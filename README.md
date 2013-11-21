# Manifold

[![Build status]][Latest build]
[![Test coverage]][Test coverage report]
[![Semantic version number]][SemVer]

* Install via [Composer] package [icecave/manifold]
* Read the [API documentation]

## What is *Manifold*?

*Manifold* is a PDO compatible facade for multi-tiered [MySQL] replication sets.

*Manifold* provides proxy PDO objects that automatically select the most
appropriate database connection to use by inspecting SQL queries and analyzing
query type and table usage. Typically *Manifold* is used in an environment where
one or more replication masters are written to depending on the tables being
used, and reads are performed on pools of multiple identical replication slaves.

*Manifold* was originally designed for use with [Doctrine's object relational
mapper], but should be suitable in most situations where a single `PDO` instance
is used.

## Configuring *Manifold*

*Manifold* features a simple configuration system for defining the structure of
a replication hierarchy, as well as which connections should be used for each
database, and connection credentials.

### The *Manifold* configuration file

The default configuration file format is [YAML], but any format supported by
[Schemer][] (i.e. [JSON] or [TOML]) will work fine. The following examples all
use [YAML].

#### The 'connections' section

```yaml
connections:
    server1: mysql:host=server1
    server2: mysql:host=server2
    server3: mysql:host=server3
```

The `connections` section defines the available server connections. All
connections should be defined here, both masters and slaves. The key is the
connection name, and the value is the [PDO DSN] for the server.

Connection credentials are not defined here. This will be explained further in
later sections.

#### The 'pools' section

```yaml
pools:
    pool1:
        - connection1
        - connection2
    pool2:
        - connection2
        - connection3
        - connection4
```

The `pools` section defines pools of related database connections. This is
primarily used to define pools of identical replication slaves for reading. The
key is the pool name, and the value is an array of connection names.

#### The 'selection' section

```yaml
selection:
    default:
        read:  primary-read-pool
        write: primary-master
    databases:
        reporting:
            read:  reporting-read-pool
            write: reporting-master
        mail_queue:
            read:  mail-read-pool
```

The `selection` section defines which connection or pool should be used when
reading and writing from each database.

For each entry in the `databases` section, the key is the name of a database,
and the value is a hash. The `read` and `write` keys of this hash can be either
a connection name, or a pool name. If either `read` or `write` is omitted,
*Manifold* will fall back to using the defined default.

The `default` key contains a hash that defines what to use when no overrides
exist for a particular database. If the `default` key is specified, both `read`
and `write` must be defined.

#### The 'replication' section

```yaml
replication:
    primary-master-a:
        secondary-master-a: null
        secondary-master-b:
            pool-a: null
        secondary-master-c:
            connection-a: null
            ternary-master-1:
                connection-b: null
                pool-b: null
    primary-master-b: null
```

The `replication` section defines the replication hierarchy. The value of this
section is a hash of replication nodes. The key is the connection name, and the
value is either a hash of slave replication nodes, or `null` to indicate that
the node has no slaves.

It is permissible to specify a pool name as a replication slave, but pools are
not allowed to have replication slaves.

To illustrate, this `replication` section is valid (assuming that `connection-a`
is a connection, and `pool-a` is a connection pool):

```yaml
replication:
    connection-a:
        pool-a: null
```

But this one is not:

```yaml
replication:
    pool-a:
        connection-a: null
```

#### Complete example configuration

The following example illustrates a complete *Manifold* configuration file in
[YAML] format:

```yaml
connections:
    master1: mysql:host=master1
    master2: mysql:host=master2
    master3: mysql:host=master3
    reporting1: mysql:host=reporting1
    slave101: mysql:host=slave101
    slave102: mysql:host=slave102
    reporting2: mysql:host=reporting2
    slave201: mysql:host=slave201
    slave202: mysql:host=slave202
    reporting3: mysql:host=reporting3
    slave301: mysql:host=slave301
    slave302: mysql:host=slave302

pools:
    pool1:
        - slave101
        - slave102
    pool2:
        - slave201
        - slave202

selection:
    default:
        read:  pool1
        write: reporting1
    databases:
        app_data:
            read:  pool1
            write: master1
        app_reporting:
            read:  pool2
            write: reporting2
        app_temp:
            read:  pool2
            write: pool2
        app_read_only:
            read: master2
        app_write_only:
            write: master2

replication:
    master1:
        master2: null
        reporting1:
            pool1: null
        reporting2:
            pool2: null
        reporting3:
            slave301: null
            slave302: null
    master3: null
```

#### Multi-file example configuration

*Manifold* also allows for configuration to be split across multiple files using
[JSON Reference] syntax. The following example is equivalent to the previous
single-file example:

```yaml
# manifold.yml
connections:
    $ref: parts/manifold.connections.yml

pools:
    $ref: parts/manifold.pools.yml

selection:
    $ref: parts/manifold.selection.yml

replication:
    $ref: parts/manifold.replication.yml
```

```yaml
# parts/manifold.connections.yml
master1: mysql:host=master1
master2: mysql:host=master2
master3: mysql:host=master3
reporting1: mysql:host=reporting1
slave101: mysql:host=slave101
slave102: mysql:host=slave102
reporting2: mysql:host=reporting2
slave201: mysql:host=slave201
slave202: mysql:host=slave202
reporting3: mysql:host=reporting3
slave301: mysql:host=slave301
slave302: mysql:host=slave302
```

```yaml
# parts/manifold.pools.yml
pool1:
    - slave101
    - slave102
pool2:
    - slave201
    - slave202
```

```yaml
# parts/manifold.selection.yml
default:
    read:  pool1
    write: reporting1
databases:
    app_data:
        read:  pool1
        write: master1
    app_reporting:
        read:  pool2
        write: reporting2
    app_temp:
        read:  pool2
        write: pool2
    app_read_only:
        read: master2
    app_write_only:
        write: master2
```

```yaml
# parts/manifold.replication.yml
master1:
    master2: null
    reporting1:
        pool1: null
    reporting2:
        pool2: null
    reporting3:
        slave301: null
        slave302: null
master3: null
```

<!-- References -->

[Doctrine's object relational mapper]: http://www.doctrine-project.org/projects/orm.html
[JSON Reference]: http://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03
[JSON]: http://en.wikipedia.org/wiki/JSON
[MySQL]: http://www.mysql.com/
[PDO DSN]: http://www.php.net/manual/en/pdo.connections.php
[Schemer]: https://github.com/eloquent/schemer
[TOML]: https://github.com/mojombo/toml
[YAML]: http://en.wikipedia.org/wiki/YAML

[API documentation]: http://icecavestudios.github.io/manifold/artifacts/documentation/api/
[Build status]: https://travis-ci.org/IcecaveStudios/manifold.png?branch=develop
[Composer]: http://getcomposer.org
[icecave/manifold]: https://packagist.org/packages/icecave/manifold
[Latest build]: https://travis-ci.org/IcecaveStudios/manifold
[Semantic version number]: http://b.repl.ca/v1/semver-0.0.0-red.png
[SemVer]: http://semver.org/
[Test coverage report]: https://coveralls.io/r/IcecaveStudios/manifold?branch=develop
[Test coverage]: https://coveralls.io/repos/IcecaveStudios/manifold/badge.png?branch=develop
