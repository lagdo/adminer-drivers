Refactoring the Adminer drivers
===============================

This repo is the result of the refactoring of the [Adminer](https://github.com/vrana/adminer) database drivers, which are originally located in this [directory](https://github.com/vrana/adminer/tree/master/adminer/drivers).

The goal is not only to use this package in [Jaxon Adminer](https://github.com/lagdo/jaxon-adminer), but also to provide one or more packages that can be easily installed with Composer.

The design
----------

Even if all the classes have been renamed and namespaced, they still implement almost the same functions.
All the global functions are also moved to new classes.

Here's how the new classes are designed.

- AdminerInterface.php
- ServerInterface.php
- ConnectionInterface.php
- DriverInterface.php
- AdminerTrait.php
- AbstractServer.php
- AbstractConnection.php
- ConnectionTrait.php
- AbstractDriver.php
- Pdo/
  - Connection.php
  - Statement.php
- [Database]/ (MySQL, PgSQL, etc.)
  - Server.php
  - Driver.php
  - [Library]/ (PDO, MySqli, etc.)
    - Connection.php
    - Statement.php

The classes
-----------

The `AbstractDriver` derived from the `Min_SQL` class from `Adminer`, defined in the `adminer/include/driver.inc.php` file.
The `AbstractServer` and `AbstractConnection` classes are created from scratch to implement functions that are common to all drivers, or provide default implementation of drivers functions.

For each specific database, the `Driver` class derived from the `Min_SQL` class, defined in the corresponding file, and the global functions are moved to the `Server` class.

For each specific library, the `Connection` and the `Statement` classes derived resp. from the `Min_DB` and `Min_Result` classes, defined in the corresponding file.
For the specific case of the `PDO` library, the common base `Connection` and `Statement` classes, in the `Pdo` dir, derived resp. from the `Min_PDO` and `Min_PDOStatement` classes from `Adminer`, defined in the `adminer/include/pdo.inc.php` file.

The interfaces
--------------

The `ServerInterface`, `ConnectionInterface` and `DriverInterface` interfaces defined the set of features that need to be implemented for each supported database.

The `AdminerInterface` defines the functions that are needed in the drivers but are not database-related, and some convenience functions which implementations are based on the drivers functions.

The traits
----------

The `ConnectionTrait` is added because the `Connection` class of the `MySqli` driver already inherits from a class provided by its library.
Then the trait is the only way to have common functions.

The `AdminerTrait` provides a `connect()` function which instanciates the correct driver classes depending on the input options, and opens a connection to the database server. It simplifies the library usage.
