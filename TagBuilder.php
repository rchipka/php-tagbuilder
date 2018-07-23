<?php
if (function_exists('vbtk_tag_builder')) {
  return;
}

function generate_js_error($e) {
  $pad_right = 1;
  $err_line = $e->getLine();
  $err_file = $e->getFile();

  $prev_lines = 3;
  $next_lines = 3;

  $excerpt = '';

  if (is_readable($err_file)) {
    $file = file($err_file);
    $lines = array();
    $padding = 10;

    for ($i = max(0, $err_line - 1 - $prev_lines); $i < $err_line + $next_lines; $i++) {
      $line = preg_replace('/\t/', '  ', $file[$i]);
      $count++;

      preg_match('/^ +/', $line, $matches);

      error_log(json_encode($matches));
      $line_padding = strlen($matches[0]);

      if ($line_padding > 0 && $line_padding < $padding) {
        $padding = $line_padding;
      }

      $line = str_pad(($i + 1) . ':', 5, ' ', $pad_right) . $line;

      if ($i == $err_line - 1) {
        $line = "\n" . $line . "\n";
      }

      array_push($lines, $line);
    }

    error_log('line pad' . $padding);
    if (sizeof($lines > 0)) {
      $excerpt = "\n\n" . implode('', preg_replace('/:\\s{' . $padding .'}/', ': ', $lines));
    }
  }

  return implode(' ', [
            '<script>(function () {',
            'var scripts = document.getElementsByTagName("script"),',
            'currentScript = scripts[scripts.length - 1];',
            'console.error(currentScript.parentNode);',
            'var e = new Error(',
             json_encode($e->getMessage() . $excerpt . "\n\n" . str_replace('wp-content/', '',str_replace(getcwd(), '',  $e->getTraceAsString()))),
             ', ',
             json_encode($e->getFile()),
             ', ',
             json_encode($e->getLine()),
             ', ',
            ');',
            'throw e;',
            '})();',
            '</script>']);;
}

function vbtk_tag_builder($tag, $attrs = [], $children = []) {
  // TODO: add filters, add context

  // $GLOBALS['vbtk_tag_builder_depth']++;

  if (is_string($attrs)) {
    $attrs = [ 'class' => $attrs ];
  }

  if (!is_array($attrs)) {
    $attrs = [$attrs];
  }

  if (!is_array($children)) {
    $children = [$children];
  }

  if ((!is_array($children) || sizeof($children) < 1) && is_array($attrs) && isset($attrs[0])) {
    $children = $attrs;
    $attrs = [];
  }

  $attributes = [];

  foreach ($attrs as $key => $value) {    
    if (is_callable($value)) {
      $value = $value();
    }

    if (is_array($value)) {
      foreach ($value as $i => $v) {
        if (is_callable($v)) {
          $value[$i] = $v();
        }
      }

      $value = implode(' ', array_filter($value));
    }

    if (is_bool($value) && !$value) {
      continue;
    }

    $value = trim(preg_replace('/\\s{2,}/', ' ', $value));

    if (is_callable('esc_attr')) {
      $value = esc_attr($value);
    }

    $attributes[] = $key . '="' . $value . '"';
  }

  if (sizeof($attrs) === 1 && isset($attrs[0])) {
    if (is_numeric($attrs[0]) || is_bool($attrs[0])) {
      if (!$attrs[0]) {
        return '';
      }
    }
  }

  $false_children = 0;

  foreach ($children as &$child) {
    if (is_callable($child)) {
      ob_start();
      
      $failed = false;

      try {
        try {
          $fail = function ($value = false, $expected = true) use (&$false_children, &$failed) {
            if (is_callable($expected)) {
              $expected = $expected($value);
            }

            if (!$failed && $value != $expected) {
              error_log(json_encode($value) . ' != ' . json_encode($expected));
              $failed = true;
              $false_children++;
            } else {

              error_log(json_encode($value) . ' == ' . json_encode($expected));
            }

            return $value;
          };

          $child = $child($fail);
        } catch (\Error $e) {
          $child = generate_js_error($e);
        }
      } catch (\Exception $e) {
        $child = generate_js_error($e);
      }

      if (!$failed) {
        if (!$child) {
          $child = ob_get_contents();
        }
      }

      ob_end_clean();
    }

    if (is_bool($child)) {
      $child = '';
      
      if ($child === false) {
        $false_children++;
      }
    } else if (is_string($child)) {

    } else if (is_numeric($child)) {

    } else if (is_array($child)) {
      if (empty($child) || isset($child[0])) {
        $child = implode("\n", $child);
      }
    } else {
      $child = print_r($child, 1);
    }
  }

  if ($false_children > 0 && sizeof($children) === $false_children) {
    $out = false;
  } else if (sizeof($attrs) < 1 && sizeof($children) == 1 && !$children[0]) {
    $out = '';
  } else {
    $out = '<' . implode(' ', array_filter([$tag, implode(' ', $attributes)]));

    if ($tag === 'img' || $tag === 'link') {
      $out .= '/>';
    } else {
      $out .= '>';
      $out .= implode("\n", $children);
      $out .= '</' . $tag . '>';
    }
  }

  return $out;
}

/* Prints: */

function _a($attrs = [], $children = []) { echo vbtk_tag_builder('a', $attrs, $children); };
function _abbr($attrs = [], $children = []) { echo vbtk_tag_builder('abbr', $attrs, $children); };
function _acronym($attrs = [], $children = []) { echo vbtk_tag_builder('acronym', $attrs, $children); };
function _address($attrs = [], $children = []) { echo vbtk_tag_builder('address', $attrs, $children); };
function _applet($attrs = [], $children = []) { echo vbtk_tag_builder('applet', $attrs, $children); };
function _area($attrs = [], $children = []) { echo vbtk_tag_builder('area', $attrs, $children); };
function _article($attrs = [], $children = []) { echo vbtk_tag_builder('article', $attrs, $children); };
function _aside($attrs = [], $children = []) { echo vbtk_tag_builder('aside', $attrs, $children); };
function _audio($attrs = [], $children = []) { echo vbtk_tag_builder('audio', $attrs, $children); };
function _b($attrs = [], $children = []) { echo vbtk_tag_builder('b', $attrs, $children); };
function _base($attrs = [], $children = []) { echo vbtk_tag_builder('base', $attrs, $children); };
function _basefont($attrs = [], $children = []) { echo vbtk_tag_builder('basefont', $attrs, $children); };
function _bdo($attrs = [], $children = []) { echo vbtk_tag_builder('bdo', $attrs, $children); };
function _big($attrs = [], $children = []) { echo vbtk_tag_builder('big', $attrs, $children); };
function _blockquote($attrs = [], $children = []) { echo vbtk_tag_builder('blockquote', $attrs, $children); };
function _body($attrs = [], $children = []) { echo vbtk_tag_builder('body', $attrs, $children); };
function _br($attrs = [], $children = []) { echo vbtk_tag_builder('br', $attrs, $children); };
function _button($attrs = [], $children = []) { echo vbtk_tag_builder('button', $attrs, $children); };
function _canvas($attrs = [], $children = []) { echo vbtk_tag_builder('canvas', $attrs, $children); };
function _caption($attrs = [], $children = []) { echo vbtk_tag_builder('caption', $attrs, $children); };
function _center($attrs = [], $children = []) { echo vbtk_tag_builder('center', $attrs, $children); };
function _cite($attrs = [], $children = []) { echo vbtk_tag_builder('cite', $attrs, $children); };
function _code($attrs = [], $children = []) { echo vbtk_tag_builder('code', $attrs, $children); };
function _col($attrs = [], $children = []) { echo vbtk_tag_builder('col', $attrs, $children); };
function _colgroup($attrs = [], $children = []) { echo vbtk_tag_builder('colgroup', $attrs, $children); };
function _datalist($attrs = [], $children = []) { echo vbtk_tag_builder('datalist', $attrs, $children); };
function _dd($attrs = [], $children = []) { echo vbtk_tag_builder('dd', $attrs, $children); };
function _del($attrs = [], $children = []) { echo vbtk_tag_builder('del', $attrs, $children); };
function _dfn($attrs = [], $children = []) { echo vbtk_tag_builder('dfn', $attrs, $children); };
function _div($attrs = [], $children = []) { echo vbtk_tag_builder('div', $attrs, $children); };
function _dl($attrs = [], $children = []) { echo vbtk_tag_builder('dl', $attrs, $children); };
function _dt($attrs = [], $children = []) { echo vbtk_tag_builder('dt', $attrs, $children); };
function _em($attrs = [], $children = []) { echo vbtk_tag_builder('em', $attrs, $children); };
function _embed($attrs = [], $children = []) { echo vbtk_tag_builder('embed', $attrs, $children); };
function _fieldset($attrs = [], $children = []) { echo vbtk_tag_builder('fieldset', $attrs, $children); };
function _figcaption($attrs = [], $children = []) { echo vbtk_tag_builder('figcaption', $attrs, $children); };
function _figure($attrs = [], $children = []) { echo vbtk_tag_builder('figure', $attrs, $children); };
function _font($attrs = [], $children = []) { echo vbtk_tag_builder('font', $attrs, $children); };
function _footer($attrs = [], $children = []) { echo vbtk_tag_builder('footer', $attrs, $children); };
function _form($attrs = [], $children = []) { echo vbtk_tag_builder('form', $attrs, $children); };
function _frame($attrs = [], $children = []) { echo vbtk_tag_builder('frame', $attrs, $children); };
function _frameset($attrs = [], $children = []) { echo vbtk_tag_builder('frameset', $attrs, $children); };
function _head($attrs = [], $children = []) { echo vbtk_tag_builder('head', $attrs, $children); };
function _header($attrs = [], $children = []) { echo vbtk_tag_builder('header', $attrs, $children); };
function _h1($attrs = [], $children = []) { echo vbtk_tag_builder('h1', $attrs, $children); };
function _h2($attrs = [], $children = []) { echo vbtk_tag_builder('h2', $attrs, $children); };
function _h3($attrs = [], $children = []) { echo vbtk_tag_builder('h3', $attrs, $children); };
function _h4($attrs = [], $children = []) { echo vbtk_tag_builder('h4', $attrs, $children); };
function _h5($attrs = [], $children = []) { echo vbtk_tag_builder('h5', $attrs, $children); };
function _h6($attrs = [], $children = []) { echo vbtk_tag_builder('h6', $attrs, $children); };
function _hr($attrs = [], $children = []) { echo vbtk_tag_builder('hr', $attrs, $children); };
function _html($attrs = [], $children = []) { echo vbtk_tag_builder('html', $attrs, $children); };
function _i($attrs = [], $children = []) { echo vbtk_tag_builder('i', $attrs, $children); };
function _iframe($attrs = [], $children = []) { echo vbtk_tag_builder('iframe', $attrs, $children); };
function _img($attrs = [], $children = []) { echo vbtk_tag_builder('img', $attrs, $children); };
function _input($attrs = [], $children = []) { echo vbtk_tag_builder('input', $attrs, $children); };
function _ins($attrs = [], $children = []) { echo vbtk_tag_builder('ins', $attrs, $children); };
function _kbd($attrs = [], $children = []) { echo vbtk_tag_builder('kbd', $attrs, $children); };
function _label($attrs = [], $children = []) { echo vbtk_tag_builder('label', $attrs, $children); };
function _legend($attrs = [], $children = []) { echo vbtk_tag_builder('legend', $attrs, $children); };
function _li($attrs = [], $children = []) { echo vbtk_tag_builder('li', $attrs, $children); };
function _link($attrs = [], $children = []) { echo vbtk_tag_builder('link', $attrs, $children); };
function _main($attrs = [], $children = []) { echo vbtk_tag_builder('main', $attrs, $children); };
function _map($attrs = [], $children = []) { echo vbtk_tag_builder('map', $attrs, $children); };
function _mark($attrs = [], $children = []) { echo vbtk_tag_builder('mark', $attrs, $children); };
function _meta($attrs = [], $children = []) { echo vbtk_tag_builder('meta', $attrs, $children); };
function _meter($attrs = [], $children = []) { echo vbtk_tag_builder('meter', $attrs, $children); };
function _nav($attrs = [], $children = []) { echo vbtk_tag_builder('nav', $attrs, $children); };
function _noscript($attrs = [], $children = []) { echo vbtk_tag_builder('noscript', $attrs, $children); };
function _object($attrs = [], $children = []) { echo vbtk_tag_builder('object', $attrs, $children); };
function _ol($attrs = [], $children = []) { echo vbtk_tag_builder('ol', $attrs, $children); };
function _optgroup($attrs = [], $children = []) { echo vbtk_tag_builder('optgroup', $attrs, $children); };
function _option($attrs = [], $children = []) { echo vbtk_tag_builder('option', $attrs, $children); };
function _p($attrs = [], $children = []) { echo vbtk_tag_builder('p', $attrs, $children); };
function _param($attrs = [], $children = []) { echo vbtk_tag_builder('param', $attrs, $children); };
function _pre($attrs = [], $children = []) { echo vbtk_tag_builder('pre', $attrs, $children); };
function _progress($attrs = [], $children = []) { echo vbtk_tag_builder('progress', $attrs, $children); };
function _q($attrs = [], $children = []) { echo vbtk_tag_builder('q', $attrs, $children); };
function _s($attrs = [], $children = []) { echo vbtk_tag_builder('s', $attrs, $children); };
function _samp($attrs = [], $children = []) { echo vbtk_tag_builder('samp', $attrs, $children); };
function _script($attrs = [], $children = []) { echo vbtk_tag_builder('script', $attrs, $children); };
function _section($attrs = [], $children = []) { echo vbtk_tag_builder('section', $attrs, $children); };
function _select($attrs = [], $children = []) { echo vbtk_tag_builder('select', $attrs, $children); };
function _small($attrs = [], $children = []) { echo vbtk_tag_builder('small', $attrs, $children); };
function _source($attrs = [], $children = []) { echo vbtk_tag_builder('source', $attrs, $children); };
function _span($attrs = [], $children = []) { echo vbtk_tag_builder('span', $attrs, $children); };
function _strike($attrs = [], $children = []) { echo vbtk_tag_builder('strike', $attrs, $children); };
function _strong($attrs = [], $children = []) { echo vbtk_tag_builder('strong', $attrs, $children); };
function _style($attrs = [], $children = []) { echo vbtk_tag_builder('style', $attrs, $children); };
function _sub($attrs = [], $children = []) { echo vbtk_tag_builder('sub', $attrs, $children); };
function _sup($attrs = [], $children = []) { echo vbtk_tag_builder('sup', $attrs, $children); };
function _table($attrs = [], $children = []) { echo vbtk_tag_builder('table', $attrs, $children); };
function _tbody($attrs = [], $children = []) { echo vbtk_tag_builder('tbody', $attrs, $children); };
function _td($attrs = [], $children = []) { echo vbtk_tag_builder('td', $attrs, $children); };
function _textarea($attrs = [], $children = []) { echo vbtk_tag_builder('textarea', $attrs, $children); };
function _tfoot($attrs = [], $children = []) { echo vbtk_tag_builder('tfoot', $attrs, $children); };
function _th($attrs = [], $children = []) { echo vbtk_tag_builder('th', $attrs, $children); };
function _thead($attrs = [], $children = []) { echo vbtk_tag_builder('thead', $attrs, $children); };
function _time($attrs = [], $children = []) { echo vbtk_tag_builder('time', $attrs, $children); };
function _title($attrs = [], $children = []) { echo vbtk_tag_builder('title', $attrs, $children); };
function _tr($attrs = [], $children = []) { echo vbtk_tag_builder('tr', $attrs, $children); };
function _u($attrs = [], $children = []) { echo vbtk_tag_builder('u', $attrs, $children); };
function _ul($attrs = [], $children = []) { echo vbtk_tag_builder('ul', $attrs, $children); };
function _var($attrs = [], $children = []) { echo vbtk_tag_builder('var', $attrs, $children); };
function _video($attrs = [], $children = []) { echo vbtk_tag_builder('video', $attrs, $children); };
function _wbr($attrs = [], $children = []) { echo vbtk_tag_builder('wbr', $attrs, $children); };

/* Returns: */

function __a($attrs = [], $children = []) { return vbtk_tag_builder('a', $attrs, $children); };
function __abbr($attrs = [], $children = []) { return vbtk_tag_builder('abbr', $attrs, $children); };
function __acronym($attrs = [], $children = []) { return vbtk_tag_builder('acronym', $attrs, $children); };
function __address($attrs = [], $children = []) { return vbtk_tag_builder('address', $attrs, $children); };
function __applet($attrs = [], $children = []) { return vbtk_tag_builder('applet', $attrs, $children); };
function __area($attrs = [], $children = []) { return vbtk_tag_builder('area', $attrs, $children); };
function __article($attrs = [], $children = []) { return vbtk_tag_builder('article', $attrs, $children); };
function __aside($attrs = [], $children = []) { return vbtk_tag_builder('aside', $attrs, $children); };
function __audio($attrs = [], $children = []) { return vbtk_tag_builder('audio', $attrs, $children); };
function __b($attrs = [], $children = []) { return vbtk_tag_builder('b', $attrs, $children); };
function __base($attrs = [], $children = []) { return vbtk_tag_builder('base', $attrs, $children); };
function __basefont($attrs = [], $children = []) { return vbtk_tag_builder('basefont', $attrs, $children); };
function __bdo($attrs = [], $children = []) { return vbtk_tag_builder('bdo', $attrs, $children); };
function __big($attrs = [], $children = []) { return vbtk_tag_builder('big', $attrs, $children); };
function __blockquote($attrs = [], $children = []) { return vbtk_tag_builder('blockquote', $attrs, $children); };
function __body($attrs = [], $children = []) { return vbtk_tag_builder('body', $attrs, $children); };
function __br($attrs = [], $children = []) { return vbtk_tag_builder('br', $attrs, $children); };
function __button($attrs = [], $children = []) { return vbtk_tag_builder('button', $attrs, $children); };
function __canvas($attrs = [], $children = []) { return vbtk_tag_builder('canvas', $attrs, $children); };
function __caption($attrs = [], $children = []) { return vbtk_tag_builder('caption', $attrs, $children); };
function __center($attrs = [], $children = []) { return vbtk_tag_builder('center', $attrs, $children); };
function __cite($attrs = [], $children = []) { return vbtk_tag_builder('cite', $attrs, $children); };
function __code($attrs = [], $children = []) { return vbtk_tag_builder('code', $attrs, $children); };
function __col($attrs = [], $children = []) { return vbtk_tag_builder('col', $attrs, $children); };
function __colgroup($attrs = [], $children = []) { return vbtk_tag_builder('colgroup', $attrs, $children); };
function __datalist($attrs = [], $children = []) { return vbtk_tag_builder('datalist', $attrs, $children); };
function __dd($attrs = [], $children = []) { return vbtk_tag_builder('dd', $attrs, $children); };
function __del($attrs = [], $children = []) { return vbtk_tag_builder('del', $attrs, $children); };
function __dfn($attrs = [], $children = []) { return vbtk_tag_builder('dfn', $attrs, $children); };
function __div($attrs = [], $children = []) { return vbtk_tag_builder('div', $attrs, $children); };
function __dl($attrs = [], $children = []) { return vbtk_tag_builder('dl', $attrs, $children); };
function __dt($attrs = [], $children = []) { return vbtk_tag_builder('dt', $attrs, $children); };
function __em($attrs = [], $children = []) { return vbtk_tag_builder('em', $attrs, $children); };
function __embed($attrs = [], $children = []) { return vbtk_tag_builder('embed', $attrs, $children); };
function __fieldset($attrs = [], $children = []) { return vbtk_tag_builder('fieldset', $attrs, $children); };
function __figcaption($attrs = [], $children = []) { return vbtk_tag_builder('figcaption', $attrs, $children); };
function __figure($attrs = [], $children = []) { return vbtk_tag_builder('figure', $attrs, $children); };
function __font($attrs = [], $children = []) { return vbtk_tag_builder('font', $attrs, $children); };
function __footer($attrs = [], $children = []) { return vbtk_tag_builder('footer', $attrs, $children); };
function __form($attrs = [], $children = []) { return vbtk_tag_builder('form', $attrs, $children); };
function __frame($attrs = [], $children = []) { return vbtk_tag_builder('frame', $attrs, $children); };
function __frameset($attrs = [], $children = []) { return vbtk_tag_builder('frameset', $attrs, $children); };
function __head($attrs = [], $children = []) { return vbtk_tag_builder('head', $attrs, $children); };
function __header($attrs = [], $children = []) { return vbtk_tag_builder('header', $attrs, $children); };
function __h1($attrs = [], $children = []) { return vbtk_tag_builder('h1', $attrs, $children); };
function __h2($attrs = [], $children = []) { return vbtk_tag_builder('h2', $attrs, $children); };
function __h3($attrs = [], $children = []) { return vbtk_tag_builder('h3', $attrs, $children); };
function __h4($attrs = [], $children = []) { return vbtk_tag_builder('h4', $attrs, $children); };
function __h5($attrs = [], $children = []) { return vbtk_tag_builder('h5', $attrs, $children); };
function __h6($attrs = [], $children = []) { return vbtk_tag_builder('h6', $attrs, $children); };
function __hr($attrs = [], $children = []) { return vbtk_tag_builder('hr', $attrs, $children); };
function __html($attrs = [], $children = []) { return vbtk_tag_builder('html', $attrs, $children); };
function __i($attrs = [], $children = []) { return vbtk_tag_builder('i', $attrs, $children); };
function __iframe($attrs = [], $children = []) { return vbtk_tag_builder('iframe', $attrs, $children); };
function __img($attrs = [], $children = []) { return vbtk_tag_builder('img', $attrs, $children); };
function __input($attrs = [], $children = []) { return vbtk_tag_builder('input', $attrs, $children); };
function __ins($attrs = [], $children = []) { return vbtk_tag_builder('ins', $attrs, $children); };
function __kbd($attrs = [], $children = []) { return vbtk_tag_builder('kbd', $attrs, $children); };
function __label($attrs = [], $children = []) { return vbtk_tag_builder('label', $attrs, $children); };
function __legend($attrs = [], $children = []) { return vbtk_tag_builder('legend', $attrs, $children); };
function __li($attrs = [], $children = []) { return vbtk_tag_builder('li', $attrs, $children); };
function __link($attrs = [], $children = []) { return vbtk_tag_builder('link', $attrs, $children); };
function __main($attrs = [], $children = []) { return vbtk_tag_builder('main', $attrs, $children); };
function __map($attrs = [], $children = []) { return vbtk_tag_builder('map', $attrs, $children); };
function __mark($attrs = [], $children = []) { return vbtk_tag_builder('mark', $attrs, $children); };
function __meta($attrs = [], $children = []) { return vbtk_tag_builder('meta', $attrs, $children); };
function __meter($attrs = [], $children = []) { return vbtk_tag_builder('meter', $attrs, $children); };
function __nav($attrs = [], $children = []) { return vbtk_tag_builder('nav', $attrs, $children); };
function __noscript($attrs = [], $children = []) { return vbtk_tag_builder('noscript', $attrs, $children); };
function __object($attrs = [], $children = []) { return vbtk_tag_builder('object', $attrs, $children); };
function __ol($attrs = [], $children = []) { return vbtk_tag_builder('ol', $attrs, $children); };
function __optgroup($attrs = [], $children = []) { return vbtk_tag_builder('optgroup', $attrs, $children); };
function __option($attrs = [], $children = []) { return vbtk_tag_builder('option', $attrs, $children); };
function __p($attrs = [], $children = []) { return vbtk_tag_builder('p', $attrs, $children); };
function __param($attrs = [], $children = []) { return vbtk_tag_builder('param', $attrs, $children); };
function __pre($attrs = [], $children = []) { return vbtk_tag_builder('pre', $attrs, $children); };
function __progress($attrs = [], $children = []) { return vbtk_tag_builder('progress', $attrs, $children); };
function __q($attrs = [], $children = []) { return vbtk_tag_builder('q', $attrs, $children); };
function __s($attrs = [], $children = []) { return vbtk_tag_builder('s', $attrs, $children); };
function __samp($attrs = [], $children = []) { return vbtk_tag_builder('samp', $attrs, $children); };
function __script($attrs = [], $children = []) { return vbtk_tag_builder('script', $attrs, $children); };
function __section($attrs = [], $children = []) { return vbtk_tag_builder('section', $attrs, $children); };
function __select($attrs = [], $children = []) { return vbtk_tag_builder('select', $attrs, $children); };
function __small($attrs = [], $children = []) { return vbtk_tag_builder('small', $attrs, $children); };
function __source($attrs = [], $children = []) { return vbtk_tag_builder('source', $attrs, $children); };
function __span($attrs = [], $children = []) { return vbtk_tag_builder('span', $attrs, $children); };
function __strike($attrs = [], $children = []) { return vbtk_tag_builder('strike', $attrs, $children); };
function __strong($attrs = [], $children = []) { return vbtk_tag_builder('strong', $attrs, $children); };
function __style($attrs = [], $children = []) { return vbtk_tag_builder('style', $attrs, $children); };
function __sub($attrs = [], $children = []) { return vbtk_tag_builder('sub', $attrs, $children); };
function __sup($attrs = [], $children = []) { return vbtk_tag_builder('sup', $attrs, $children); };
function __table($attrs = [], $children = []) { return vbtk_tag_builder('table', $attrs, $children); };
function __tbody($attrs = [], $children = []) { return vbtk_tag_builder('tbody', $attrs, $children); };
function __td($attrs = [], $children = []) { return vbtk_tag_builder('td', $attrs, $children); };
function __textarea($attrs = [], $children = []) { return vbtk_tag_builder('textarea', $attrs, $children); };
function __tfoot($attrs = [], $children = []) { return vbtk_tag_builder('tfoot', $attrs, $children); };
function __th($attrs = [], $children = []) { return vbtk_tag_builder('th', $attrs, $children); };
function __thead($attrs = [], $children = []) { return vbtk_tag_builder('thead', $attrs, $children); };
function __time($attrs = [], $children = []) { return vbtk_tag_builder('time', $attrs, $children); };
function __title($attrs = [], $children = []) { return vbtk_tag_builder('title', $attrs, $children); };
function __tr($attrs = [], $children = []) { return vbtk_tag_builder('tr', $attrs, $children); };
function __u($attrs = [], $children = []) { return vbtk_tag_builder('u', $attrs, $children); };
function __ul($attrs = [], $children = []) { return vbtk_tag_builder('ul', $attrs, $children); };
function __var($attrs = [], $children = []) { return vbtk_tag_builder('var', $attrs, $children); };
function __video($attrs = [], $children = []) { return vbtk_tag_builder('video', $attrs, $children); };
function __wbr($attrs = [], $children = []) { return vbtk_tag_builder('wbr', $attrs, $children); };
