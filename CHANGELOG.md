## 3.0.0 2023-03-13
- Support for Laravel 10

## 2.1.0 2022-04-01
- Added support for PHP 8.1
- Added support for Laravel 9
- You can now specify the unit for suspension period (days or minutes). The third argument of the `suspend`  method takes an optional unit period.

## 2.0.0 2021-09-13
- Support for PHP 8

## 1.2.0 - 2021-01-11
- Support for Laravel 8

## 1.1.1 - 2020-08-05
- Fixed `nonActiveSuspensions` scope to include models that don't have a suspensions relationship.

## 1.1.0 - 2020-08-04
- Added query scope `activeSuspensions` to get all active suspensions
- Added query scope `nonActiveSuspensions` to get all non-active suspensions

## 1.0.1 - 2020-07-29
- fixed issue with model suspension check for instances when model is not currently suspended

## 1.0.0 - 2020-07-24
- initial release.
