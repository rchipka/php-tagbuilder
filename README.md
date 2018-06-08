# PHP TagBuilder
A lightweight HTML tag building API for PHP


## Benefits:

 * No more HTML mangling and switching between <?php mode

 * No more inline attribute logic/toggling
 
 * Less painful echoing, concatenating

 * Easily store HTML tags in a variable
 
 * Impossible to write malformed HTML (PHP brackets require balance)

 * Auto-escaped attribute values

 * Able hook/filter all elements/content/attributes
 
 * Integration with VBTK Context for granular hooking/filtering
 
 * Won't show tags with empty content (no more if !empty() logic)

## Usage:

_*tagname*()   <-  echo html
__*tagname*()  <-  return html


### Args:

_*tagname*($attributes, $content)


$attributes - optional - key/value array of attributes

   * values can be strings, numbers, or arrays

   * given a plain sequential array (numeric keys), values will default to
   the `class` attr

   * if an attribute is set to a key/value array, then the value will determine
   whether to include the corresponding key (useful for class toggling)


$content - optional - value or array of values

   * values can be strings, functions or an array of strings and functions
   
   
## Examples

```php
<?php
_ul( [ 'class' =>  [ 'no-bullet', 'sidebar-links' ] ], function () {
  while ( have_rows( 'links' ) ) : the_row();
    _li( function () {
      _h5( get_sub_field('title') );
      _ul( [ 'class' => [ 'no-bullet', 'sub-links' ] ], function () {
        foreach (get_sub_field('links') as $link) :
          _li(
            __a([ 'href' => $link['url'] ], $link['title'] )
          );
        endforeach;
      });
    });
  endwhile;
});
```
