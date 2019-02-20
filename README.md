# PHP-STL
本库灵感来自node-stl
Parse STL files with PHP and get volume, weight, and the bounding box.

# example
```php
  $stl = new Stl(array(
    'file_path' => 'chosse/your/file/path'  
  ));
  echo $stl->getVolume();
  echo $stl->getArea();
  var_dump($stl->getBoundingBox());
```

php-stl recognizes by itself whether it is dealing with an ASCII STL or a binary STL file

# license

MIT

# version

0.1.0


