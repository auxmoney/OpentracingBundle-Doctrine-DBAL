# auxmoney OpentracingBundle - Doctrine DBAL

![release](https://github.com/auxmoney/OpentracingBundle-Doctrine-DBAL/workflows/release/badge.svg)
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
    composer req auxmoney/opentracing-bundle-doctrine-dbal
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

You can optionally configure environment variables, however, the default configuration will run fine out of the box for most DBAL based applications.
If you cannot change environment variables in your project, you can alternatively overwrite the container parameters directly.

| environment variable | container parameter | type | default | description |
|---|---|---|---|---|
| AUXMONEY_OPENTRACING_DOCTRINE_FULL_STATEMENT | auxmoney_opentracing.doctrine.tag_full_statement | `string` | `true` | whether to add a tag with the full SQL statement to the span |
| AUXMONEY_OPENTRACING_DOCTRINE_PARAMETERS | auxmoney_opentracing.doctrine.tag_parameters | `string` | `true` | whether to add a tag with the statement parameters to the span |
| AUXMONEY_OPENTRACING_DOCTRINE_ROW_COUNT | auxmoney_opentracing.doctrine.tag_row_count | `string` | `false` | whether to add a tag with the affected / returned rows to the span; see [limitations section](#limitations) |
| AUXMONEY_OPENTRACING_DOCTRINE_USER | auxmoney_opentracing.doctrine.tag_user | `string` | `false` | whether to add a tag with the connection username to the span |

Hint: you can use `true`, `on`, `yes` or `1` to enable an environment variable.

## Usage

When querying databases using Doctrine DBAL (or higher level packages like Doctrine ORM), spans reflecting these queries are automatically generated and added to the trace. The generated tags can contain:

| tag name | contains |
|---|---|
| db.statement | the executed statement |
| db.parameters | the parameters of the executed statement, if present |
| db.row_count | affected / returned rows of the executed statement; see [limitations section](#limitations) |
| db.user | the username of the decorated DBAL connection | 

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
