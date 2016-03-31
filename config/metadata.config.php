<?php
/**
 * Novius Blocks
 *
 * @copyright  2014 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link       http://www.novius-os.org
 */

return array(
    'name'          => 'Blocks',
    'version'       => '5.0',
    'i18n_file' => 'novius_blocks::default',
    'icons'         => array(
        64 => 'static/apps/novius_blocks/img/64-blocks.png',
        32 => 'static/apps/novius_blocks/img/32-blocks.png',
        16 => 'static/apps/novius_blocks/img/16-blocks.png',
    ),
    'permission'    => array(),
    'provider'      => array(
        'name' => 'Novius',
    ),
    'requires'      => array('novius_renderers'),
    'namespace'     => 'Novius\Blocks',
    'launchers'     => array(
        'novius_blocks' => array(
            'name'   => 'Blocks',
            'action' => array(
                'action' => 'nosTabs',
                'tab'    => array(
                    'url' => 'admin/novius_blocks/block/appdesk',
                ),
            ),
        ),
    ),
    'enhancers'     => array(
        'blocks' => array(
            'title'      => 'Blocks',
            'id'         => 'blocks',
            'desc'       => '',
            'enhancer'   => 'novius_blocks/front/block/main',
            'previewUrl' => 'admin/novius_blocks/block/enhancer/preview',
            'dialog'     => array(
                'contentUrl' => 'admin/novius_blocks/block/enhancer/popup',
                'ajax'       => true,
                'width'      => 700,
                'height'     => 500,
            ),
        ),
    ),
    'data_catchers' => array(
        'novius_blocks' => array(
            'title'            => 'Blocks',
            'description'      => 'Put forward this element',
            'action'           => array(
                'action' => 'nosTabs',
                'tab'    => array(
                    'url'   => 'admin/novius_blocks/block/crud/insert_update/?context={{context}}&title={{urlencode:'.\Nos\DataCatcher::TYPE_TITLE.'}}&summary={{urlencode:'.\Nos\DataCatcher::TYPE_TEXT.'}}&thumbnail={{urlencode:'.\Nos\DataCatcher::TYPE_IMAGE.'}}&absolute_url={{urlencode:absolute_url}}',
                    'label' => 'Block',
                ),
            ),
            'onDemand'         => true,
            'specified_models' => false,
            'required_data'    => array(
                \Nos\DataCatcher::TYPE_TITLE,
            ),
            'optional_data'    => array(
                \Nos\DataCatcher::TYPE_TEXT,
                \Nos\DataCatcher::TYPE_IMAGE,
                \Nos\DataCatcher::TYPE_URL,
            ),
        ),
    ),
);
