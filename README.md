# Manifold

[![Build Status]](https://travis-ci.org/IcecaveStudios/manifold)
[![Test Coverage]](https://coveralls.io/r/IcecaveStudios/manifold?branch=develop)
[![SemVer]](http://semver.org)

**Manifold** is a PDO compatible facade for multi-tiered [MySQL] replication
sets.

**Manifold** provides proxy PDO objects that automatically select the most
appropriate database connection to use by inspecting SQL queries and analyzing
query type and table usage. Typically **Manifold** is used in an environment
where one or more replication masters are written to depending on the tables
being used, and reads are performed on pools of multiple identical replication
slaves.

**Manifold** was originally designed for use with [Doctrine's object relational
mapper], but should be suitable in most situations where a single `PDO` instance
is used.

* Install via [Composer](http://getcomposer.org) package [icecave/manifold](https://packagist.org/packages/icecave/manifold)
* Read the [API documentation](http://icecavestudios.github.io/manifold/artifacts/documentation/api/)

## Table of contents

- [Configuring Manifold](#configuring-manifold)
    - [The Manifold configuration file](#the-manifold-configuration-file)
        - [The 'connections' section](#the-connections-section)
        - [The 'pools' section](#the-pools-section)
        - [The 'selection' section](#the-selection-section)
        - [The 'replication' section](#the-replication-section)
        - [Reading a configuration file](#reading-a-configuration-file)
        - [Complete example configuration](#complete-example-configuration)
        - [Multi-file example configuration](#multi-file-example-configuration)
    - [Configuring Manifold credentials](#configuring-manifold-credentials)
        - [Environment variable credentials](#environment-variable-credentials)
        - [File-based credentials](#file-based-credentials)
            - [Reading a credentials file](#reading-a-credentials-file)
        - [Connecting without credentials](#connecting-without-credentials)
        - [Injecting the credentials provider](#injecting-the-credentials-provider)

## Configuring Manifold

**Manifold** features a simple configuration system for defining the structure of
a replication hierarchy, as well as which connections should be used for each
database, and connection credentials.

### The Manifold configuration file

The default configuration file format is [YAML], but any format supported by
[Schemer][] (i.e. [JSON] or [TOML]) will work fine. The following examples all
use [YAML].

For a detailed description of the configuration file, see the
[configuration schema].

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
**Manifold** will fall back to using the defined default.

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

#### Reading a configuration file

To read a configuration file, use an instance of [CachingConfigurationReader]:

```php
use Icecave\Manifold\Configuration\Caching\CachingConfigurationReader;

$reader = new CachingConfigurationReader;
$configuration = $reader->readFile('/path/to/manifold.yml');
```

The caching configuration reader will look for a PHP file at
`<configuration-filename>.cache.php` containing an opcode-cacheable version of
the configuration file, and create one if it does not exist. This file allows
**Manifold** to avoid any disk I/O and validation overhead to vastly reduce load
times.

#### Complete example configuration

The following example illustrates a complete **Manifold** configuration file in
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

**Manifold** also allows for configuration to be split across multiple files using
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

### Configuring Manifold credentials

**Manifold** uses a credentials *provider* system to manage the usernames and
passwords used to connect to each server specified in the main configuration
file. Any object implementing [CredentialsProviderInterface] can be used, but
**Manifold** comes with a couple of providers by default:

#### Environment variable credentials

To read credentials from environment variables, create an instance of
[EnvironmentCredentialsProvider]:

```php
use Icecave\Manifold\Authentication\Credentials;
use Icecave\Manifold\Authentication\EnvironmentCredentialsProvider;

$provider = new EnvironmentCredentialsProvider(
    new Credentials('DB_USERNAME', 'DB_PASSWORD')
);
```

In the above example, `DB_USERNAME` and `DB_PASSWORD` are environment variable
names containing the actual credentials. **Manifold** will retrieve the actual
credentials at run-time in order to establish a connection.

In order to use different credentials for specific connections, simply provide
an associative array of connection name to credentials as the second argument:

```php
$provider = new EnvironmentCredentialsProvider(
    new Credentials('DB_USERNAME', 'DB_PASSWORD'),
    array(
        'reporting' => new Credentials(
            'DB_USERNAME_REPORTING',
            'DB_PASSWORD_REPORTING'
        ),
        'mail-queue' => new Credentials(
            'DB_USERNAME_MAIL',
            'DB_PASSWORD_MAIL'
        ),
    )
);
```

Usernames and/or passwords can also be omitted if desired:

```php
$provider = new EnvironmentCredentialsProvider(new Credentials('DB_USERNAME'));
```

#### File-based credentials

**Manifold** supports a simple configuration file for defining credentials.
Similar to the main configuration file, the credentials file is capable of
supporting multiple formats, and even being split across multiple files. The
following is a simple example using [YAML] format:

```yaml
default:
    username: default-username
    password: default-password

connections:
    reporting:
        username: reporting-username
        password: reporting-password

    mail-queue:
        username: mail-username
        password: mail-password
```

The `connections` defines a hash, where the key is the name of the connection
defined in the main configuration file, and the value is a hash containing the
username and password to use when connecting.

The `default` key defines the username and password to use when an entry is not
defined for a specific connection.

In all cases, the username or password can be omitted, which tells **Manifold**
to connect without explicitly using that credential.

##### Reading a credentials file

To read credentials from a file, use an instance of [CachingCredentialsReader]:

```php
use Icecave\Manifold\Authentication\Caching\CachingCredentialsReader;

$reader = new CachingCredentialsReader;
$provider = $reader->readFile('/path/to/manifold-credentials.yml');
```

Using the caching credentials reader will look for a PHP file at
`<credentials-filename>.cache.php` containing an opcode-cacheable version of the
credentials file, and create one if it does not exist. This file allows
**Manifold** to avoid any disk I/O and validation overhead to vastly reduce load
times.

#### Connecting without credentials

To make **Manifold** connect without using explicit credentials, simply create
an instance of [CredentialsProvider] with no arguments:

```php
use Icecave\Manifold\Authentication\CredentialsProvider;

$provider = new CredentialsProvider;
```

#### Injecting the credentials provider

The configuration reader requires that the credentials provider be present
before the configuration is loaded. This example demonstrates the process of
creating a configuration reader with a custom credentials provider:

```php
use Icecave\Manifold\Authentication\Credentials;
use Icecave\Manifold\Authentication\EnvironmentCredentialsProvider;
use Icecave\Manifold\Configuration\Caching\CachingConfigurationReader;
use Icecave\Manifold\Connection\ConnectionFactory;

$provider = new EnvironmentCredentialsProvider(
    new Credentials('DB_USERNAME', 'DB_PASSWORD')
);
$reader = new CachingConfigurationReader(new ConnectionFactory($provider));
```

<!-- References -->
[Build Status]: http://img.shields.io/travis/IcecaveStudios/manifold/develop.svg
[Test Coverage]: http://img.shields.io/coveralls/IcecaveStudios/manifold/develop.svg
[SemVer]: http://img.shields.io/:semver-0.0.0-red.svg

[CachingConfigurationReader]: src/Icecave/Manifold/Configuration/Caching/CachingConfigurationReader.html
[CachingCredentialsReader]: src/Icecave/Manifold/Authentication/Caching/CachingCredentialsReader.html
[configuration schema]: res/schema/manifold-configuration-schema.yml
[credentials schema]: res/schema/manifold-credentials-schema.yml
[CredentialsProvider]: src/Icecave/Manifold/Authentication/CredentialsProvider.html
[CredentialsProviderInterface]: src/Icecave/Manifold/Authentication/CredentialsProviderInterface.html
[Doctrine's object relational mapper]: http://www.doctrine-project.org/projects/orm.html
[EnvironmentCredentialsProvider]: src/Icecave/Manifold/Authentication/EnvironmentCredentialsProvider.html
[JSON Reference]: http://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03
[JSON]: http://en.wikipedia.org/wiki/JSON
[MySQL]: http://www.mysql.com/
[PDO DSN]: http://www.php.net/manual/en/pdo.connections.php
[Schemer]: https://github.com/eloquent/schemer
[TOML]: https://github.com/mojombo/toml
[YAML]: http://en.wikipedia.org/wiki/YAML
