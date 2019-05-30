# MultilingualPress 2 to 3 Migration
A WP plugin that allows migrating data from MultilingualPress version 2 to version 3.

## Installation
The migration tool is a WP CLI command, shipped as a regular WP plugin.
Install it as you would any other plugin, i.e. in any of the following ways:

- If a build is available, you can install from ZIP.
- Install with Composer: `composer require inpsyde/multilingualpress2to3:^0.1`.
- Clone the repo into your `plugins` directory.

## Requirements

1. MLP3 must contain the changes made in [`eebfc1b`][`inpsyde/multilingualpress@eebfc1b`].

    This is necessary in order to satisfy requirement **4**.

1. MLP2 must contain the changes in [`7dccc9c`][`inpsyde/MultilingualPress@7dccc9c`].

    This is necessary in order to prevent automatic deletion of the `site_relations` table
    by MLP2 on uninstall. This table has the same name in MLP2 and MLP3, and therefore
    should remain after MLP2 is uninstalled.

1. This plugin must be active.

    It registers the WP CLI command, and is also necessary in order to satisfy requirement **4**.

1. MLP3 must be active during migration.

    - The tool assumes that the tables to migrate the data into have already been created.
    - The tool uses some info exposed by MLP3 classes:
        * Languages table structure, to create the temporary table.
        
1. All options tables must have the same collation.

    This is necessary in order to run a `UNION` query on them, which is needed for
    migrating redirections.

## Usage
In the root of your WP installation, run the following command to see all available arguments and flags:

```
wp help mlp2to3
```

## Known Limitations

1. When migrating the language repository, most languages will be migrated.

    Ideally, only the custom (modified) languages would be migrated. However,
    in the current state it is not possible to determine which languages are
    different from their defaults. Due to inconsistencies between language
    defaults in MLP2 vs MLP3, the best possible comparison strategy determines
    most MLP2 languages to be different from those in MLP3 defaults. This
    results in the custom languages being migrated, but also over a hundred
    others.

## Development
This project uses Docker for testing and development. Therefore, you must have [Docker Toolbox][]
or [Docker Desktop][] - whichever is suitable for your platform - in order to use the bundled
development and testing environment.

### Dependency Management
After you have cloned the project, you need to install dependencies compatible with the project's
target environment. The environment already exists, and is the `test` Docker Compose service.
If you are on Windows and using MinTTY/GitBash or similar, you may want to use the `winpty`
utility by prefixing commands to be run in docker with it.

#### Authentication

Before this, however, you will need to configure Composer authentication. This is because some
of the dependencies are private packages (currently the MLP3 plugin package), and therefore
this project uses Packagist.com. Obviously for technical details the authentication details
are missing from this package, and you will need to configure your own. Run the following in
the project root:

```
composer config --auth http-basic.repo.packagist.com <your-username> <your-token>
```

This will create an `auth.json` file with these credentials, which is set to be ignored by Git -
these are your personal details, do not commit them!

#### Installing Deps

After you have configured authentication, just run `composer install` in the `test` container:

```
docker-compose run test composer install
```

Alternatively, when you need to update dependencies (perhaps you have added some), run this instead:


```
docker-compose run test composer update
```

### View Website
If you need to preview something visual during development, you need to access a WordPress test site.
This is set up as the `wordpress` service. Just access the IP of your Docker machine.

```
$ docker-machine ip
192.168.99.100
```

If you are using some native solution instead of explicitly using Docker Machine, the IP is most likely
your `localhost`.

### Access Database
The database lives in the `mysql` service. You can connect to it using your preferred client on
the IP of the host machine (see above for explanation), on port `8082`. Use the `root` user with
password `wordpress`.

Alternatively, you can use phpMyAdmin by accessing the machine at port `1234`, which will be
handled by the `phpmyadmin` service.

### Testing
Testing of the project is also handled by appropriate Docker services.

#### Acceptance
We use Codeception to run acceptance tests, and this is what the `codecept` service is for.

Use the below command to run the `acceptance` suite in the `codecept` service:

```
docker-compose run codecept run acceptance
```


[Docker Toolbox]: https://docs.docker.com/toolbox/
[Docker Desktop]: https://www.docker.com/products/docker-desktop

[`inpsyde/multilingualpress@eebfc1b`]: https://bitbucket.org/inpsyde/multilingualpress/commits/eebfc1b9caba54e028afc491fd3005d722a89995
[`inpsyde/MultilingualPress@7dccc9c`]: https://github.com/inpsyde/MultilingualPress/commit/7dccc9ce10b0f361369e4987371312d859a9d73c
