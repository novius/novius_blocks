<?php
/**
 * Novius Blocks
 *
 * @copyright  2014 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

namespace Novius\Blocks;

use Nos\Nos;
use Nos\Tools_Wysiwyg;

class Controller_Front_Block extends \Nos\Controller_Front_Application
{
    /**
     *
     *
     * @param array $args
     * @return bool|\Fuel\Core\View
     */
    public function action_main($args = array())
    {
        // Get the selected display
        $display_id = \Arr::get($args, 'display', 'default');

        // Generate the blocks in the selected display
        $blocks = static::generate_display($display_id, $args);
        if (empty($blocks)) {
            return false;
        }

        // Return the blocks wrapped in the selected display type
        return \View::forge($this->config['views'][$args['display_type']], array(
            'enhancer_args' => $args,
            'blocks' => $blocks,
        ), false);
    }

    /**
     * Generate the view containing the blocks in the selected display
     *
     * @param $display_id
     * @param $args
     * @return bool|\Fuel\Core\View
     */
    public static function generate_display($display_id, $args) {
        // Get the available displays
        $displays = Display::available();
        if (empty($displays)) {
            return false;
        }

        // The selected display is a view ?
        $display = \Arr::get($displays, 'views.'.$display_id);
        if (!empty($display)) {
            return \View::forge(\Arr::get($display, 'view'), array(
                'enhancer_args' => $args,
                'blocks'     => self::get_blocks($args),
            ), false);
        }

        // The selected display is a model ?
        $display = \Arr::get($displays, 'model.'.$display_id);
        if (!empty($display)) {
            $display_config = \Config::load('novius_blocks::display', true);
            return \View::forge(\Arr::get($display_config, 'view'), array(
                'display'       => $display,
                'enhancer_args' => $args,
                'blocks'        => self::get_blocks($args),
            ), false);
        }

        return false;
    }

    /**
     * @param $args
     * @return array
     */
    public static function get_blocks($args)
    {
        $blocks = array();
        switch ($args['display_type']) {
            case 'blocks' :
                if (!empty($args['blocks_ids'])) {
                    $blocks_tmp = Model_Block::find('all', array(
                        'where' => array(
                            array('block_id', 'in', $args['blocks_ids'])
                        ),
                    ));
                    $blocks = array();
                    foreach ($args['blocks_ids'] as $id) {
                        $blocks[$blocks_tmp[$id]->block_id] = $blocks_tmp[$id];
                    }
                }
                break;
            case 'column' :
                if (!empty($args['blco_id'])) {
                    $column = Model_Column::find($args['blco_id']);
                    $blocks_tmp = $column->blocks;
                    if (!empty($column->blco_blocks_ordre)) {
                        $ordre = (array) unserialize($column->blco_blocks_ordre);
                        $ids = array();
                        foreach ($blocks_tmp as $tmp_block) {
                            $ids[$tmp_block->block_id] = $tmp_block->block_id;
                        }
                        foreach ($ordre as $block_id) {
                            if (in_array($block_id, $ids)) {
                                $blocks[] = $blocks_tmp[$block_id];
                                unset($ids[$block_id]);
                            }
                        }
                        if (count($ids)) {
                            foreach ($ids as $block_id) {
                                $blocks[] = $blocks_tmp[$block_id];
                            }
                        }
                    }
                }
                break;
        }
        return $blocks;
    }

    /**
     * Display a block
     *
     * @param $block
     * @return bool|mixed
     */
    public static function display_block($block)
    {
        if (empty($block)) {
            return false;
        }

        // Get the template configuration
        $templates_config = \Config::load('novius_blocks::templates', true);
        $template_config = \Arr::get($templates_config, $block->block_template);
        if (empty($template_config)) {
            return false;
        }

        // Get the block configuration
        $config = Model_Block::init_config($template_config, $block->block_template);

        // Append the stylesheet
        if (($stylesheet = \Arr::get($config, 'css'))) {
            $main_controller = \Nos\Nos::main_controller();
            if ($main_controller && method_exists($main_controller, 'addCss')) {
                \Nos\Nos::main_controller()->addCss($stylesheet);
            } else {
                ?>
                <link rel="stylesheet" href="<?= $stylesheet ?>" />
                <?php
            }
        }

        // Append the custom stylesheets (backoffice)
        if (NOS_ENTRY_POINT == Nos::ENTRY_POINT_ADMIN) {
            $files = array(
                'static/css/blocks/admin/'.$block->block_template.'.preview.css',
                'static/css/blocks/admin/'.$block->block_template.'.css'
            );
            foreach ($files as $file) {
                if (file_exists(DOCROOT.$file)) {
                    ?>
                    <link rel="stylesheet" href="<?= $file ?>" />
                    <?php
                    break;
                }
            }
        }

        return static::get_block_view($block, $config, $block->block_template);
    }

    /**
     * @param Model_Block $block
     * @param $config
     * @param $name
     * @return mixed
     */
    public static function get_block_view(Model_Block $block, $config, $name)
    {
        // Image
        $image = '';
        if (!empty($block->medias->image)) {
            $image = strtr($config['image_params']['tpl'], array(
                '{src}' => $block->medias->image->get_public_path_resized($config['image_params']['width'], $config['image_params']['height']),
                '{title}' => $block->block_title,
            ));
        }

        // Description (wysiwyg)
        $description = static::parse_wysiwyg($block->wysiwygs->description);

        // CSS class
        if (!empty($block->block_class)) {
            $config['class'] = trim(\Arr::get($config, 'class').' '.$block->block_class);
        }

        // Forge the view
        $view = \View::forge($config['view'], array(
            'config'        => $config,
            'description'   => $description,
            'title'         => $block->block_title,
            'link'          => $block->get_url(),
            'link_title'    => $block->block_link_title,
            'link_new_page' => $block->block_link_new_page,
            'image'         => $image,
            'block'          => $block
        ), false)->render();

        // Replace the placeholders
        $view = strtr($view, array(
            '{title}'       => $block->block_title,
            '{name}'        => $name,
            '{description}' => $description,
            '{link}'        => $block->get_url(),
            '{link_title}'  => $block->block_link_title,
            '{image}'       => $image,
            '{class}'       => \Arr::get($config, 'class'),
        ));

        return $view;
    }

    protected static function parse_wysiwyg($content) {
        if (NOS_ENTRY_POINT == Nos::ENTRY_POINT_ADMIN) {
            //custom parse in back office
            //avoid replacing enhancers by changing the callback
            Tools_Wysiwyg::parseEnhancers(
                $content,
                function ($enhancer, $config, $tag) use (&$content) {
//TODO make the Core accept hmvc calls on action_preview (should not return ajax content) => would allow to display a preview
//                    $prev_url = \Nos\Config_Data::get('enhancers.'.$enhancer.'.previewUrl', null);
                    $title = \Nos\Config_Data::get('enhancers.'.$enhancer.'.title', null);
//                    if (!empty($prev_url)) {
//                        $config = html_entity_decode($config);
//                        $function_content = Nos::hmvc($prev_url, array(\Format::forge($config, 'json')->to_array()));
//                        $content = str_replace($tag, $function_content, $content);
//                    } else {
                        $content = str_replace($tag, '<h2>'.$title.'<h2>', $content);
//                    }
                }
            );

            Tools_Wysiwyg::parse_medias(
                $content,
                function ($media, $params) use (&$content) {
                    if (empty($media)) {
                        if ($params['tag'] == 'img') {
                            // Remove dead images
                            $content = str_replace($params['content'], '', $content);
                        } elseif ($params['tag'] == 'a') {
                            // Remove href for links (they become anchor)?
                            // http://stackoverflow.com/questions/11144653/a-script-links-without-href
                            //$content = str_replace('href="'.$params['url'].'"', '', $content);
                        }
                    } else {
                        if (!empty($params['height'])) {
                            $media_url = $media->urlResized($params['width'], $params['height']);
                        } else {
                            $media_url = $media->url();
                        }
                        $new_content = preg_replace('`'.preg_quote($params['url'], '`').'(?!\d)`u', Tools_Url::encodePath($media_url), $params['content']);
                        $content = str_replace($params['content'], $new_content, $content);
                    }
                }
            );
            //and it's useless to deal with links (see the original parse_wysiwyg)
            return $content;
        } else {
            //classic parse in front (deal with enhancers)
            return Tools_Wysiwyg::parse($content);
        }
    }
}