# NSKeyedUnarchiver for PHP

This library provides an independent implementation of Apple's [`NSKeyedUnarchiver`](https://developer.apple.com/documentation/foundation/nskeyedunarchiver)
tool. It allows for unpacking of application data saved by various first- and third-party applications running on both
macOS and iOS with its derivatives.

### What it is?
The library should able to handle any archive produced by [`NSKeyedArchiver`](https://developer.apple.com/documentation/foundation/nskeyedarchiver)
regardless of the complexity of the object graph. It fully supports both XML and binary archives. Additionally, the code
was designed with extensibility in mind with very little assumptions made along the way. By default the library comes
bundles with [various data types natively present in Apple's Foundation framework](https://github.com/kiler129/NSKeyedUnarchiver/tree/master/src/DTO/Native),
but any 3rd-party objects can be mapped as well. In addition, for simpler usecases, archives can be unpacked to a nested
array structure.


### Installation
Use [Composer](https://getcomposer.org):

```shell
composer require noflash/nskeyedunarchiver
```

### Usage
See [`examples`](examples) directory.
