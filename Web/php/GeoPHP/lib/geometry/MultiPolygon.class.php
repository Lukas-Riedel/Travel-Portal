<?php
/**
 * MultiPolygon: A collection of Polygons
 */
class MultiPolygon extends Collection
{
  protected $geom_type = 'MultiPolygon';
  
  public function pointInPolygon($point,$c = false){
    foreach($this->components as $component){
      $c = $component->pointInPolygon($point,$c);
    }
    return $c;
  }
  
}
