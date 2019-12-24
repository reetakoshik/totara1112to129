
# Add new palette

1. Add new setting in lib/settings/Theme.php. Find method palette() and modify variable $palettes:
```php
    $palettes = [
        'blue'   => get_string('settings-theme-palette-gray', static::THEME), 
        'green'  => get_string('settings-theme-palette-green', static::THEME),
        'red'    => get_string('settings-theme-palette-red', static::THEME), 
        'orange' => get_string('settings-theme-palette-orange', static::THEME),

        // here is the new one setting
        'gray'   => get_string('settings-theme-palette-gray', static::THEME) 
    ];
```

2. Go to lib.php and modify theme_golearningzone_process_css. Add actual rgb color:
```php
    function theme_golearningzone_process_css($css, $theme) 
    {
        /* ...some previous code here... */

        $css = str_replace('[[setting:gray_r]]', 175, $css);
        $css = str_replace('[[setting:gray_g]]', 175, $css);
        $css = str_replace('[[setting:gray_b]]', 175, $css);

        /* it's important to return $css at the end */

        return $css;
    }
```

3. Add new css palette. Create file style/palette_gray.css. The content simply copy from one existing palette and replace color name. For example:
```css
    [data-palette="blue"] .nav-tabs > li.active > a,
    [data-palette="blue"] .nav-tabs > li.active > a:hover,
    [data-palette="blue"] #dialog-tabs .tabs li.ui-tabs-active.ui-state-active a,
    [data-palette="blue"] #dialog-tabs .tabs li.ui-tabs-active.ui-state-active a:hover {
      border-top: 3px solid rgba([[setting:blue_r]], [[setting:blue_g]], [[setting:blue_b]], 1);
    }
```
    will become in palette_gray.css:
```css
    [data-palette="gray"] .nav-tabs > li.active > a,
    [data-palette="gray"] .nav-tabs > li.active > a:hover,
    [data-palette="gray"] #dialog-tabs .tabs li.ui-tabs-active.ui-state-active a,
    [data-palette="gray"] #dialog-tabs .tabs li.ui-tabs-active.ui-state-active a:hover {
      border-top: 3px solid rgba([[setting:gray_r]], [[setting:gray_g]], [[setting:gray_b]], 1);
    }
```

4. Don't forget to add new css file to config.php


