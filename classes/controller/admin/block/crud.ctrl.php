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

use Fuel\Core\Input;

/**
 * Class Controller_Admin_Block_Crud
 *
 * @property Model_Block item
 * @package Novius\Blocks
 */
class Controller_Admin_Block_Crud extends \Nos\Controller_Admin_Crud
{
    /** @var $block_template used to store the block template for a new block */
    protected $block_template = null;

    protected function addStylesheets()
    {
        $stylesheets = \Arr::get($this->config, 'css');
        if (!empty($stylesheets)) {
            foreach ($stylesheets as $stylesheet) {
                $main_controller = \Nos\Nos::main_controller();
                if ($main_controller && method_exists($main_controller, 'addCss')) {
                    \Nos\Nos::main_controller()->addCss($stylesheet);
                } else {
                    ?>
                    <link rel="stylesheet" href="<?= $stylesheet ?>"/>
                <?php
                }
            }
        }
    }

    public function action_insert_update($id = null, $block_template = null)
    {
        if (!is_numeric($id)) {
            $block_template = $id;
            $id             = null;
        }
        // The url is changed in JS afterwards, so we need to intercept the block template at this point
        if (!empty($block_template)) {
            $item                 = $this->crud_item($id);
            $item->block_template = $block_template;
            $this->block_template = $block_template;
        }
        return parent::action_insert_update($id);
    }


    /**
     * @param null $id
     *
     * @return \Fuel\Core\View|\Nos\View
     */
    public function action_form($id = null)
    {
        $context                                              = Input::get('context', !empty($this->item) ? $this->item->block_context : null);
        $this->config['fields']['columns']['form']['options'] = \Arr::assoc_to_keyval(\Novius\Blocks\Model_Column::find('all', array('where' => array('blco_context' => $context))), 'blco_id', 'blco_title');
        $this->item                                           = $this->crud_item($id);
        $this->is_new                                         = $this->item->is_new();
        if ($this->is_new) {
            $this->init_item();
            if (!empty($this->block_template)) {
                $this->item->block_template = $this->block_template;
            }
        }
        // When we are saving the block, be sure we are using the good template with the good fields.
        $postedTpl = \Input::post('block_template');
        if (!empty($postedTpl)) {
            $this->item->block_template = $postedTpl;
        }


        $this->clone = clone $this->item;
        $this->checkPermission($this->is_new ? 'add' : 'edit');

        $blockConfig = $this->item->getConfig();
        $toMerge     = array('layout' => "layout.0.params", 'fields' => 'fields');
        foreach ($toMerge as $source => $destination) {
            \Arr::set($this->config, $destination,
                \Arr::merge(
                    \Arr::get($this->config, $destination, array()),
                    \Arr::get($blockConfig, "crud.$source", array())
                )
            );
        }

        // Hide the block bar when a template is selected
        if (!empty($this->item->block_template)) {
            if (\Arr::get($this->config, 'block_type.collapse_when_selected', true)) {
                \Arr::set($this->config, "layout.0.params.content.template.params.options.expanded", false);
            }
            $layoutExpanderKey = "layout.0.params.content.template.params.title";
            \Arr::set($this->config, $layoutExpanderKey, \Arr::get($this->config, $layoutExpanderKey, '')." : ".$blockConfig['title']);
        } else {
            //select template first
            //if template no chosen, don't show the title input
            \Arr::set($this->config, "layout.0.params.title", array());
        }

        $this->config['layout_insert'] = $this->config['layout'];
        $this->config['layout_update'] = $this->config['layout'];

        $fields   = $this->fields($this->config['fields']);
        $fieldset = \Fieldset::build_from_config($fields, $this->item, $this->build_from_config());
        $fieldset = $this->fieldset($fieldset);

        $view_params             = $this->view_params();
        $view_params['fieldset'] = $fieldset;

        // We can't do this form inside the view_params() method, because additional vars (added
        // after the reference was created) won't be available from the reference
        $view_params['view_params'] = & $view_params;

        $this->addStylesheets();
        return \View::forge($this->config['views'][$this->is_new ? 'insert' : 'update'], $view_params, false);
    }

}
