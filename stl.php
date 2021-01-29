<?php

/**
* Vector3
*/
class Vector3
{
  public $x;
  public $y;
  public $z;
  function __construct($x, $y, $z)
  {
    $this->x = $x;
    $this->y = $y;
    $this->z = $z;
  }

  public function clone()
  {
    return new Vector3($this->x, $this->y, $this->z);
  }

  public function add($v) {
    $this->x = $this->x + $v->x;
    $this->y = $this->y + $v->y;
    $this->z = $this->z + $v->z;
    return $this;
  }

  public function sub($v) {
    $this->x = $this->x - $v->x;
    $this->y = $this->y - $v->y;
    $this->z = $this->z - $v->z;
    return $this;
  }

  /**
   * 计算自身与$v的积
   * @param  Vector3 $v
   * @return float 乘积
   */
  public function dot($v) {
    return $this->x * $v->x +
      $this->y * $v->y +
      $this->z * $v->z;
  }

  public function cross($v) {
    $x = $this->x;
    $y = $this->y;
    $z = $this->z;

    $this->x = $y * $v->z - $z * $v->y;
    $this->y = $z * $v->x - $x * $v->z;
    $this->z = $x * $v->y - $y * $v->x;

    return $this;
  }

  public function length() {
    return sqrt($this->x * $this->x + $this->y * $this->y + $this->z * $this->z);
  }
}

/**
 * 三角形
 */
class Triangle
{
  public $vert1;
  public $vert2;
  public $vert3;

  /**
   * 三角形构造函数
   * @param Vector3d $a 点A
   * @param Vector3d $b 点b
   * @param Vector3d $c 点c
   */
  function __construct($a = null, $b = null, $c = null)
  {
    $this->vert1 = $a;
    $this->vert2 = $b;
    $this->vert3 = $c;
  }
}

define('STL_UNPACK_MAX_STEP', 256);
define('STL_HEADER_LENGTH', 80);
define('STL_DATA_OFFSET', 84);
define('STL_FACE_LENGTH', 50); // 12 * 4 + 2;

/**
* PHP STL
*/
class Stl
{
  private $volume;
  private $weight;
  private $verteces;
  /**
   * 
   */
  function __construct($params)
  {
    if (!array_key_exists('file_path', $params)) {
      return false;
    }
    $buf = file_get_contents($params['file_path']);
    if (!$buf) {
      return false;
    }
    $isAscii = true;
    $len = strlen($buf);
    $step = min(STL_UNPACK_MAX_STEP, $len);
    $offset = 0;
    while($step > 0 && $isAscii) {
      $cbuf = unpack("@$offset/C$step", $buf);
      foreach ($cbuf as $b) {
        if ($b > 127) {
          $isAscii = false;
          break;
        }
      }

      $offset += $step;
      $len -= $offset;
      $step = min(STL_UNPACK_MAX_STEP, $len);
    }

    if ($isAscii) {
      $this->_parseSTLString($buf);
    } else {
      $this->_parseSTLBinary($buf);
    }
  }

  private function _parseSTLString($stl) {
    $totalVol = 0;
    preg_match_all(
        '/facet\s+normal\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+outer\s+loop\s+vertex\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+vertex\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+vertex\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
        '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+endloop\s+endfacet/', $stl, $vertexes);

    $len = count($vertexes);
    $verteces = array();
    foreach ($vertexes[0] as $key => $vert) {
        $preTriangle = new Triangle();
        preg_match_all(
            '/vertex\s+'.
            '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
            '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s+'.
            '([-+]?\b(?:[0-9]*\.)?[0-9]+(?:[eE][-+]?[0-9]+)?\b)\s/', $vert, $iVertexes);
        foreach ($iVertexes[0] as $iKey => $vertex) {
          preg_match_all('/[-+]?[0-9]*\.?[0-9]+/', str_replace('vertex', '', $vertex), $tVector3);
          $preVector3 = new Vector3($tVector3[0][0], $tVector3[0][1], $tVector3[0][2]);
          $name = 'vert' . ($iKey + 1);
          $preTriangle->$name = $preVector3;
        }
        $preVolume = $this->_triangleVolume($preTriangle);
        $totalVol +=  floatval($preVolume);
        $verteces[$key] = $preTriangle;
    }

    $volumeTotal = abs($totalVol);

    $this->volume = $volumeTotal;
    $this->weight = $volumeTotal * 1.04;
    $this->verteces = $verteces;
  }

  private function _parseSTLBinary($buf) {
    $numTriangles = unpack("@" . STL_HEADER_LENGTH . "/Icount", $buf);
    $len = intval($numTriangles['count']);
    $totalVol = 0;
    for ($i=0; $i < $len; $i++) {
      $offset = STL_DATA_OFFSET + STL_FACE_LENGTH * $i;
      $v3d = unpack("@$offset/f3n/f3a/f3b/f3c", $buf);
      $triangle = new Triangle(
        new Vector3($v3d['a1'], $v3d['a2'], $v3d['a3']),
        new Vector3($v3d['b1'], $v3d['b2'], $v3d['b3']),
        new Vector3($v3d['c1'], $v3d['c2'], $v3d['c3'])
      );
      $totalVol += $this->_triangleVolume($triangle);
      $verteces[$i] = $triangle;
    }

    $volumeTotal = abs($totalVol);

    $this->volume = $volumeTotal;
    $this->weight = $volumeTotal * 1.04;
    $this->verteces = $verteces;
  }

  private function _triangleVolume ($triangle) {
    $v321 = $triangle->vert3->x * $triangle->vert2->y * $triangle->vert1->z;
    $v231 = $triangle->vert2->x * $triangle->vert3->y * $triangle->vert1->z;
    $v312 = $triangle->vert3->x * $triangle->vert1->y * $triangle->vert2->z;
    $v132 = $triangle->vert1->x * $triangle->vert3->y * $triangle->vert2->z;
    $v213 = $triangle->vert2->x * $triangle->vert1->y * $triangle->vert3->z;
    $v123 = $triangle->vert1->x * $triangle->vert2->y * $triangle->vert3->z;

    return (1.0/6.0)*(-$v321 + $v231 + $v312 - $v132 - $v213 + $v123);
  }

  private function _boundingBox($triangles) {
    $len = count($triangles);
    echo "\n"; var_dump($len); echo "\n";
    if ($len === 0) return array(0,0,0);

    $minx = false;
    $maxx = false;
    $miny = false;
    $maxy = false;
    $minz = false;
    $maxz = false;

    $tminx = false;
    $tmaxx = false;
    $tminy = false;
    $tmaxy = false;
    $tminz = false;
    $tmaxz = false;

    foreach ($triangles as $key => $triangle) {

      $tminx = min(min($triangle->vert1->x, $triangle->vert2->x), $triangle->vert3->x);
      $minx = $minx === false ? $tminx :  ($tminx < $minx ? $tminx : $minx);
      $tmaxx = max(max($triangle->vert1->x, $triangle->vert2->x), $triangle->vert3->x);
      $maxx = $maxx === false ? $tmaxx : ($tmaxx > $maxx ? $tmaxx : $maxx);

      $tminy = min(min($triangle->vert1->y, $triangle->vert2->y), $triangle->vert3->y);
      $miny = $miny === false ? $tminy : ($tminy < $miny ? $tminy : $miny);
      $tmaxy = max(max($triangle->vert1->y, $triangle->vert2->y), $triangle->vert3->y);
      $maxy = $maxy === false ? $tmaxy : ($tmaxy > $maxy ? $tmaxy : $maxy);

      $tminz = min(min($triangle->vert1->z, $triangle->vert2->z), $triangle->vert3->z);
      $minz = $minz === false ? $tminz : ($tminz < $minz ? $tminz : $minz);
      $tmaxz = max(max($triangle->vert1->z, $triangle->vert2->z), $triangle->vert3->z);
      $maxz = $maxz === false ? $tmaxz : ($tmaxz > $maxz ? $tmaxz : $maxz);
    }



    return array(
      $maxx - $minx,
      $maxy - $miny,
      $maxz - $minz
    );
  }

  public function getBoundingBox() {
    return $this->_boundingBox($this->verteces);
  }

  private function _surfaceArea($triangles) {
    $len = count($triangles);
    if ($len === 0) return 0.0;

    $_area = 0.0;
    for ($i=0; $i < $len; $i++) { 
      $va = $triangles[$i]->vert1;
      $vb = $triangles[$i]->vert2;
      $vc = $triangles[$i]->vert3;

      $ab = $vb->clone()->sub($va);
      $ac = $vc->clone()->sub($va);

      $cross = $ab->clone()->cross($ac);
      $_area += $cross->length() / 2;
    }

    return $_area;
  }

  public function getArea() {
    return $this->_surfaceArea($this->verteces);
  }

  public function getAreaUnit() {
    return 'square milimeter';
  }

  public function getVolume() {
    return $this->volume;
  }
  public function getVolumeUnit() {
    return 'Cubic millimeter';
  }
  public function getWeight() {
    return $this->weight;
  }
  public function getWeightUnit() {
    return 'gram';
  }
}