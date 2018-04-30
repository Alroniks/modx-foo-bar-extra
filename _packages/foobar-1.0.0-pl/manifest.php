<?php return array (
  'manifest-version' => '1.1',
  'manifest-attributes' => 
  array (
    'changelog' => 'This file shows the changes in recent releases of FooBar.

FooBar 1.0.0-pl (TDB)
===================================
- Nothing
',
    'license' => 'The MIT License (MIT)
Copyright (c) 2018 Ivan Klimchuk <ivan@klimchuk.com>
TEXT OF LICENSE
',
    'readme' => '# FooBar
Example of implementation of encrypted package for modstore.pro repository.
',
    'requires' => 
    array (
      'php' => '>=5.5',
      'modx' => '>=2.6',
    ),
  ),
  'manifest-vehicles' => 
  array (
    0 => 
    array (
      'vehicle_package' => 'transport',
      'vehicle_class' => 'xPDOFileVehicle',
      'class' => 'xPDOFileVehicle',
      'guid' => '260ffcbf5a84da7d1fe49bfa1f5a7525',
      'native_key' => NULL,
      'filename' => 'xPDOFileVehicle/8b05e897129aff5d3d651dd318971192.vehicle',
    ),
    1 => 
    array (
      'vehicle_package' => 'transport',
      'vehicle_class' => 'xPDOScriptVehicle',
      'class' => 'xPDOScriptVehicle',
      'guid' => '9a8b493ad3b8f85fe015cb0e12b50451',
      'native_key' => NULL,
      'filename' => 'xPDOScriptVehicle/1b98e1cbfab540910f1c3ce593e458ba.vehicle',
    ),
    2 => 
    array (
      'vehicle_package' => 'transport',
      'vehicle_class' => 'EncryptedVehicle',
      'class' => 'modNamespace',
      'guid' => '53d04102dad11ea22ed1a54c1644465c',
      'native_key' => NULL,
      'filename' => 'modNamespace/864c32b50e215d5669b1e2c1a161cdaa.vehicle',
      'namespace' => 'foobar',
    ),
    3 => 
    array (
      'vehicle_package' => 'transport',
      'vehicle_class' => 'EncryptedVehicle',
      'class' => 'modSystemSetting',
      'guid' => 'ede0625a4561c954699fbd757e3e1fac',
      'native_key' => NULL,
      'filename' => 'modSystemSetting/ec7b3a48e1cd588b60ceeb361c3b30e6.vehicle',
      'namespace' => 'foobar',
    ),
  ),
);