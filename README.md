# auxmoney OpentracingBundle - Doctrine DBAL

![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/auxmoney/OpentracingBundle-Doctrine-DBAL)
![Travis (.org)](https://img.shields.io/travis/auxmoney/OpentracingBundle-Doctrine-DBAL)
![Coveralls github](https://img.shields.io/coveralls/github/auxmoney/OpentracingBundle-Doctrine-DBAL)
![Codacy Badge](https://api.codacy.com/project/badge/Grade/5ccaae3d94cf41c68ad8de83ddcbca1a)
![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/auxmoney/OpentracingBundle-Doctrine-DBAL)
![Scrutinizer code quality (GitHub/Bitbucket)](https://img.shields.io/scrutinizer/quality/g/auxmoney/OpentracingBundle-Doctrine-DBAL)
![GitHub](https://img.shields.io/github/license/auxmoney/OpentracingBundle-Doctrine-DBAL)

This bundle adds automatic spanning for Doctrine DBAL connections to the [OpentracingBundle](https://github.com/auxmoney/OpentracingBundle-core).

## Installation

### Prerequisites

This bundle is only an additional plugin and should not be installed independently. See
[its documentation](https://github.com/auxmoney/OpentracingBundle-core#installation) for more information on installing the OpentracingBundle first.

### Require dependencies

After you have installed the OpentracingBundle:

* require the dependencies:

```bash
    composer req auxmoney/opentracing-bundle-doctrine-dbal:^0.3
```

### Enable the bundle

If you are using [Symfony Flex](https://github.com/symfony/flex), you are all set!

If you are not using it, you need to manually enable the bundle:

* add bundle to your application:

```php
    # Symfony 3: AppKernel.php
    $bundles[] = new Auxmoney\OpentracingDoctrineDBALBundle\OpentracingDoctrineDBALBundle();
```

```php
    # Symfony 4+: bundles.php
    Auxmoney\OpentracingDoctrineDBALBundle\OpentracingDoctrineDBALBundle::class => ['all' => true],
```

## Configuration

TODO!

## Usage

When querying databases using Doctrine DBAL (or higher level packages like Doctrine ORM), spans reflecting these queries are automatically generated and added to the trace. The generated tags can contain:

| tag name | contains |
|---|---|
| db.user | the username of the decorated DBAL connection | 
| db.statement | the executed statement |
| db.parameters | the parameters of the executed statement, if present |
| db.row_count | affected rows / returned rows of the executed statement; see [limitations section](#limitations) |

## Limitations

* `db.row_count`: the correctness of this value depends heavily on the implementation of the `Doctrine\DBAL\Driver\Statement` of the driver for the database.
For example, if you are using a PDO driver, keep in mind: "For most databases, PDOStatement::rowCount() does not return the number of rows 
affected by a SELECT statement.". See [the official PHP documentation](https://www.php.net/manual/en/pdostatement.rowcount.php) for more information on this particular topic.
If you are getting different results than expected, consult the documentation for the used driver.

## Development

Be sure to run

```bash
    composer run-script quality
```

every time before you push code changes. The tools run by this script are also run in the CI pipeline.
