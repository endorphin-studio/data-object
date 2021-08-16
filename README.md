Create object from array and work with it with helper functions 
getFieldName(), setFieldName($value)

## Code Status
[![Latest Stable Version](https://poser.pugx.org/endorphin-studio/data-object/v/stable)](https://packagist.org/packages/endorphin-studio/data-object)
[![Total Downloads](https://poser.pugx.org/endorphin-studio/data-object/downloads)](https://packagist.org/packages/endorphin-studio/data-object)
[![License](https://poser.pugx.org/endorphin-studio/data-object/license)](https://packagist.org/packages/endorphin-studio/data-object)
[![Build Status](https://travis-ci.org/endorphin-studio/data-object.svg?branch=4.0)](https://travis-ci.org/endorphin-studio/data-object)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/endorphin-studio/data-object/badges/quality-score.png)](https://scrutinizer-ci.com/g/endorphin-studio/data-object/)

## About
	Author: Serhii Nekhaienko
	Email: serhii.nekhaienko@gmail.com
	Stable Version: 1.0.0
	License: MIT


## Requirements
	PHP >=7.4
	JSON extension

## Install via Composer
    composer require endorphin-studio/data-object
## Basic Usage
```php
use EndorphinStudio\DataObject\DataObject;

class Role extends DataObject {

}

class User extends DataObject {
    protected array $fieldTypeMapping = [
        'roles' => Role::class
    ];
    
    protected array $listFields = [
        'roles'
    ];
}

$userData = [
    'name' => 'Serhii',
    'login' => 'serhii',
    'roles' => [
        [
            'name' => 'admin'
        ],
        [
            'name' => 'editor'
        ]
    ]
];

$user = new User($userData);

echo $user->getName(); // Serhii
echo $user->getLogin(); // serhii

foreach($user->getRoles() as $role) {
    echo $role->getName(); // admin, editor
}
