# Composer PSR-3 Logger [![Build Status](https://travis-ci.org/kiler129/ComposerPsr3.svg?branch=master)](https://travis-ci.org/kiler129/ComposerPsr3)
Small, fast and PSR-3 compliant logging library useful with custom Composer scripts.

### Requirements
  * PHP >=5.6

### Installation
Add package to `require-dev` and use ;-)

### Usage
Initialize `Logger` object in your Composer hook and optionally set custom verbosity levels - evetyhing else is automatic.

Available methods:
  * **emergency/alert/...(message, context)** - Every log level have method named after it. So if you want to log "warning" just use `Shout->warning("Be warned!")`. Second argument can be array with any information possible to represent as string by (formatted by [print_r()](http://php.net/print_r)).
  * **log(level, message, context)** - It have the same effect as methods described below, so calling `Logger->log("warning", "Be warned!")` produces the same result as example above.

### Configuration
Shout comes preconfigured by default, but allows to configure almost anything. List below specifies configuration methods along with default values (specified in brackets). 
  * **setLineFormat(\<%1$s\> [%2$s] %3$s [%4$s] [%5$s])** - How line should be formated. You can use 6 modifiers: 
    * %1$s - date
    * %2$s - log level (uppercased)
    * %3$s - message text
    * %4$s - context (formatted by [print_r()](http://php.net/print_r)) 
    * %1$d - unix timestamp
  * **setDatetimeFormat(d.m.Y H:i:s)** - It accepts any [date()](http://php.net/date) compliant format.
  * **setLevelVerbosity(level, value)** - In fact PSR-3 states custom log levels are forbidden, but this logger supports them. By default messages with custom level uses verbosity defined by `IOInterface::NORMAL`. This method allows setting custom one (and even change builtin levels verbosity, which is NOT recommended).
