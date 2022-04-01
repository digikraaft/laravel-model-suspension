# Suspend Eloquent models
![tests](https://github.com/digikraaft/laravel-model-suspension/workflows/tests/badge.svg)
[![Build Status](https://scrutinizer-ci.com/g/digikraaft/laravel-model-suspension/badges/build.png?b=master)](https://scrutinizer-ci.com/g/digikraaft/laravel-model-suspension/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/digikraaft/laravel-model-suspension/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/digikraaft/laravel-model-suspension/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/digikraaft/laravel-model-suspension/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

Imagine you want to suspend an Eloquent model, a User model for example. Usually, adding
an `is_suspended` field to the model could work. But then you can't keep track of why the model was suspended,
how many times or even set the duration of the suspension.

This package provides a `CanBeSuspended` trait that enables you do all of these, once installed. It would be
something like this:

```
//suspend model
$model->suspend();

//suspend for the next 7 days
$model->suspend(7);

//suspend for the next 7 days with a reason
$model->suspend(7, 'privacy violation');

//get the latest suspension
$model->suspension(); //returns an instance of \Digikraaft\ModelSuspension\Suspension

//get the reason for suspension
$model->suspension()->reason; //returns 'privacy violation'
```

## Installation

You can install the package via composer:

```bash
composer require digikraaft/laravel-model-suspension
```
You must publish the migration with:
```bash
php artisan vendor:publish --provider="Digikraaft\ModelSuspension\ModelSuspensionServiceProvider" --tag="migrations"
```
Run the migration to publish the `suspensions` table with:
```
php artisan migrate
```
You can optionally publish the config-file with:
```
php artisan vendor:publish --provider="Digikraaft\ModelSuspension\ModelSuspensionServiceProvider" --tag="config"
```
The content of the file that will be published to `config/model-suspension.php`:
``` 
return [
    /*
      * The class name of the suspension model that holds all suspensions.
      *
      * The model must be or extend `Digikraaft\ModelSuspension\Suspension`.
      */
    'suspension_model' => Digikraaft\ModelSuspension\Suspension::class,

    /*
     * The name of the column which holds the ID of the model related to the suspensions.
     *
     * Only change this value if you have set a different name in the migration for the suspensions table.
     */
    'model_primary_key_attribute' => 'model_id',

];
```
## Usage
Add the `CanBeSuspended` trait to the model you would like to suspend:
```php
use Digikraaft\ModelSuspension\CanBeSuspended;
use Illuminate\Database\Eloquent\Model;

class EloquentModel extends Model
{
    use CanBeSuspended;
}
```

### Suspend model
You can suspend a model like this:
``` 
$model->suspend();
```
The number of days to be suspended for with a reason can be passed as the first
and second arguments respectively:
``` 
//suspend model for the next 7 days with a reason
$model->suspend(7, 'optional reason');
```

You can also specify the suspension period (in minutes or days) with an optional third argument:
``` 
//suspend model for the next 30 minutes with a reason
$model->suspend(30, 'optional reason', Suspension::PERIOD_IN_MINUTES);
```

### Retrieving suspensions
You can get the current suspension of the model like this:
```
$model->suspension(); //returns the latest instance of Digikraaft\ModelSuspension\Suspension
```
All associated suspensions of a model can be retrieved like this:
```
$model->suspensions();
```
The `allSuspensions` scope can be used to retrieve all the suspensions of the model:
```
$allSuspensions = EloquentModel::allSuspensions();
```

The `activeSuspensions` scope can be used to retrieve only active suspensions:
```
$allActiveSuspensions = EloquentModel::activeSuspensions();
```

The `nonActiveSuspensions` scope can be used to retrieve only non-active suspensions:
```
$allNonActiveSuspensions = EloquentModel::nonActiveSuspensions();
```
### Get number of times a model has been suspended
You can get the number of times a model has been suspended like this:
```
$model->numberOfTimesSuspended();
```
To get the number of times a model has been suspended over a period,
pass in a `Carbon` formatted `$from` and `$to` dates as the first and second
arguments respectively:
```
//get the number of times a model has been suspended over the last month

$from = now()->subMonth();
$to = now();

$model->numberOfTimesSuspended($from, $to);
```
Note that an `InvalidDate` exception will be thrown if the `$from` date is later than the `$to`

### Check if model has been suspended

You can check if a model is currently suspended:

```
$model->isSuspended();
```

You can also check if a model has ever been suspended:
```
$model->hasEverBeenSuspended();
```

### Unsuspend model
You can unsuspend a model at any time by using the `unsuspend` method:
```
$model->unsuspend();
```
This will `unsuspend` the model immediately. If a suspension period has
been initially specified, it will be overridden.

### Events
The `Digikraaft\ModelSuspension\Events\ModelSuspensionChanged` event will be dispatched when 
a model is suspended or unsuspended.
```
namespace Digikraaft\ModelSuspension\Events;

use Digikraaft\ModelSuspension\Suspension;
use Illuminate\Database\Eloquent\Model;

class ModelSuspensionChanged
{
    /** @var \Digikraaft\ModelSuspension\Suspension */
    public Suspension $suspension;

    /** @var \Illuminate\Database\Eloquent\Model */
    public Model $model;

    public function __construct(Model $model, Suspension $suspension)
    {
        $this->suspension = $suspension;
        $this->model = $model;
    }
}
```

### Custom model and migration
You can change the model used by specifying a different class name in the 
`suspension_model` key of the `model-suspension` config file.

You can also change the column name used in the suspension table 
(default is `model_id`) when using a custom migration. If this is the case,
also change the `model_primary_key_attribute` key of the `model-suspension` config file.

## Testing
Use the command below to run your tests:
``` bash
composer test
```

## More Good Stuff
Check [here](https://github.com/digikraaft) for more awesome free stuff!

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security
If you discover any security related issues, please email dev@digitalkraaft.com instead of using the issue tracker.

## Credits
- [Tim Oladoyinbo](https://github.com/timoladoyinbo)
- [All Contributors](../../contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
