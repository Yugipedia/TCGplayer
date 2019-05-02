# TCGplayer

Mediawiki extension for embedding info provided by TCGplayer.

## Features
Implements the TCGplayer API to list card prices on _TCG_ card pages.


## Development on Linux (OS X anyone?)
To take advantage of this automation, use the Makefile: `make help`. To start,
run `make install` and follow the instructions.

## Development on Windows
If you cannot use the `Makefile`, do the following:

* Install nodejs, npm, and PHP composer
* Change to the extension's directory
* npm install
* composer install

Once set up, running `npm test` and `composer test` will run automated code checks.
