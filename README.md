# Manifold

[![Build Status]](https://travis-ci.org/IcecaveStudios/manifold)
[![Test Coverage]](https://coveralls.io/r/IcecaveStudios/manifold?branch=develop)
[![SemVer]](http://semver.org)

**Manifold** is a PDO compatible facade for multi-tiered MySQL replication sets.

**Manifold** provides proxy PDO objects that automatically select the most appropriate database connection to use by
inspecting SQL queries and analysing query type and table usage. Typically **Manifold** is used in an environment where
one or more replication masters are written to depending on the tables being used, and reads are performed on pools of
multiple identical replication slaves.

**Manifold** was originally designed for use with [Doctrine's Object Relation Mapper](http://www.doctrine-project.org/projects/orm.html),
but should be suitable in most situations where a single `PDO` instance is used.

* Install via [Composer](http://getcomposer.org) package [icecave/manifold](https://packagist.org/packages/icecave/manifold)
* Read the [API documentation](http://icecavestudios.github.io/manifold/artifacts/documentation/api/)

<!-- references -->
[Build Status]: https://travis-ci.org/IcecaveStudios/manifold.png?branch=develop
[Test Coverage]: https://coveralls.io/repos/IcecaveStudios/manifold/badge.png?branch=develop
[SemVer]: http://calm-shore-6115.herokuapp.com/?label=semver&value=0.0.0&color=red
