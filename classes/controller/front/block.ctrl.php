<?php
/**
 * Novius Blocks
 *
 * @copyright  2014 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link       http://www.novius-os.org
 */

namespace Novius\Blocks;

use Nos\Nos;

class Controller_Front_Block extends \Nos\Controller_Front_Application
{
    /**
     *
     *
     * @param array $args
     *
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
            'blocks'        => $blocks,
        ), false);
    }

    /**
     * Generate the view containing the blocks in the selected display
     *
     * @param $display_id
     * @param $args
     *
     * @return bool|\Fuel\Core\View
     */
    public static function generate_display($display_id, $args)
    {
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
                'blocks'        => self::get_blocks($args),
            ), false);
        }

        // The selected display is a model ?
        $display = \Arr::get($displays, 'model.'.$display_id);
        if (!empty($display)) {
            $display_config = \Config::load('novius_blocks::display', true);
            $type           = 'view';
            if (NOS_ENTRY_POINT === Nos::ENTRY_POINT_ADMIN) {
                $type = 'preview';
            }
            return \View::forge(\Arr::get($display_config, $type), array(
                'display'       => $display,
                'enhancer_args' => $args,
                'blocks'        => self::get_blocks($args),
            ), false);
        }

        return false;
    }

    /**
     * @param $args
     *
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
                    $blocks     = array();
                    foreach ($args['blocks_ids'] as $id) {
                        if(isset($blocks_tmp[$id])) {
                            $blocks[$blocks_tmp[$id]->block_id] = $blocks_tmp[$id];
                        }
                    }
                }
                break;
            case 'column' :
                if (!empty($args['blco_id'])) {
                    $column     = Model_Column::find($args['blco_id']);
                    $blocks_tmp = $column->blocks;
                    if (!empty($column->blco_blocks_ordre)) {
                        $ordre = (array)unserialize($column->blco_blocks_ordre);
                        $ids   = array();
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
     *
     * @return bool|mixed
     */
    public static function display_block($block)
    {
        if (empty($block)) {
            return false;
        }
        $template_config = Model_Block::config($block->block_template);
        return static::get_block_view($block, $template_config, $block->block_template);
    }

    /**
     * @param Model_Block $block
     * @param             $config
     * @param             $name
     *
     * @return mixed
     */
    public static function get_block_view(Model_Block $block, $config, $name)
    {

        // CSS class
        if (!empty($block->block_class)) {
            $config['class'] = trim(\Arr::get($config, 'class').' '.$block->block_class);
        }

        $type = 'view';
        if (NOS_ENTRY_POINT === Nos::ENTRY_POINT_ADMIN) {
            $type = 'preview';
        }

        return \View::forge($config[$type], array(
            'config'   => $config,
            'title'    => $block->block_title,
            'item'     => $block,
            'template' => $config['template'],
        ), false)->render();
    }
}
