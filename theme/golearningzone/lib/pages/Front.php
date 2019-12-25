<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;

class Front extends Base
{
    use ThemeTrait;

    public function render()
    {   
        $blocksSizes = $this->calculateBlocksSizes();
        $template = 'theme_golearningzone/front_page';
        $params = $this->getDefaultPageValues() + [
            'is_editing'    => $this->renderer->page->user_is_editing(),
            'side-pre'      => has_capability('theme/golearningzone:viewadminblock', \context_system::instance()) ? $this->renderer->blocks('side-pre') : '',
            'first'         => $this->blocks('first', $blocksSizes[0][0]),
            'second-left'   => $this->blocks('second-left', $blocksSizes[1][0]),
            'second-right'  => $this->blocks('second-right', $blocksSizes[1][1]),
            'third-left'    => $this->blocks('third-left', $blocksSizes[2][0]),
            'third-center'  => $this->blocks('third-center', $blocksSizes[2][1]),
            'third-right'   => $this->blocks('third-right', $blocksSizes[2][2]),
            'fourth-left'   => $this->blocks('fourth-left', $blocksSizes[3][0]),
            'fourth-center' => $this->blocks('fourth-center', $blocksSizes[3][1]),
            'fourth-right'  => $this->blocks('fourth-right', $blocksSizes[3][2])
        ];
        //echo '<pre>';print_r($this->blocks('first', $blocksSizes[0][0]));echo '</pre>';
        //die('test123');
        return $this->renderer->render_from_template($template, $params);  
    }

    private function calculateBlocksSizes()
    {
        $defaultBlocksSizes = [
            [12],
            [8, 4],
            [4, 4, 4],
            [4, 4, 4]
        ];

        $renderer = $this->renderer;

        if ($renderer->page->user_is_editing()) {
            return $defaultBlocksSizes;
        }

        function rowBlockSize(array $defaultSizes) {
            $args = func_get_args();
            array_shift($args);
            $columns = $args;

            if (count($columns) !== count($defaultSizes)) {
                throw new Exception("Diffrent count of columns and sizes", 1);
            }

            $sizes = $defaultSizes;
            $hasEmptyColumns = false;
            $displayedColumnsCount = 0;
            foreach ($columns as $columnNumber => $column) {
                if (!$column) {
                    $sizes[$columnNumber] = 0;
                    $hasEmptyColumns = true;
                } else {
                    $displayedColumnsCount++;
                }
            }

            if ($hasEmptyColumns) {
                $newSize = $displayedColumnsCount ? 12 / $displayedColumnsCount : 0;
                foreach ($sizes as $columnNumber => $size) {
                    if ($size) {
                        $sizes[$columnNumber] = $newSize;
                    }
                }
            }

            return $sizes;
        }

        $renderer = $this->renderer;

        $blocksSizes = [
            0 => rowBlockSize(
                $defaultBlocksSizes[0],
                $renderer->page->blocks->region_has_content('first', $renderer)
            ),
            1 => rowBlockSize(
                $defaultBlocksSizes[1],
                $renderer->page->blocks->region_has_content('second-left', $renderer),
                $renderer->page->blocks->region_has_content('second-right', $renderer)
            ),
            2 => rowBlockSize(
                $defaultBlocksSizes[2],
                $renderer->page->blocks->region_has_content('third-left', $renderer),
                $renderer->page->blocks->region_has_content('third-center', $renderer),
                $renderer->page->blocks->region_has_content('third-right', $renderer)
            ),
            3 => rowBlockSize(
                $defaultBlocksSizes[3],
                $renderer->page->blocks->region_has_content('fourth-left', $renderer),
                $renderer->page->blocks->region_has_content('fourth-center', $renderer),
                $renderer->page->blocks->region_has_content('fourth-right', $renderer)
            )
        ];

        return $blocksSizes;
    }

    private function blocks($name, $width = 12)
    {
        $renderer = $this->renderer;
        $block = $renderer->blocks($name);

         if (!$width || !$block) {
             return '';
         }

        return $renderer->render_from_template(
            'theme_golearningzone/front_page_block_wrapper',
            [
                'size'  => $width,
                'name'  => $name,
                'block' => $block,
            ]
        );
    }

}
