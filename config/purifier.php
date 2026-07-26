<?php

/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,

    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',

            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[style|class|width|height|alt|src],table[style|class|border|cellpadding|cellspacing|width|height],thead,tbody,tfoot,tr[style|class|width|height],th[style|class|colspan|rowspan|width|height|border],td[style|class|colspan|rowspan|width|height|border]',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding,padding-left,padding-right,padding-top,padding-bottom,margin,margin-left,margin-right,margin-top,margin-bottom,color,background-color,text-align,float,width,height,border,border-left,border-right,border-top,border-bottom,border-collapse,border-spacing',

            'CSS.MaxImgLength' => null,

            'AutoFormat.AutoParagraph' => true,

            // Important for tables.
            // If true, empty td/tr/table elements can be removed.
            'AutoFormat.RemoveEmpty' => false,

            'URI.AllowedSchemes' => [
                'data' => true,
                'http' => true,
                'https' => true,
            ],
        ],

        'test' => [
            'Attr.EnableID' => 'true',
        ],

        'youtube' => [
            'HTML.SafeIframe' => 'true',
            'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
        ],

        'custom_definition' => [
            'id' => 'html5-definitions',
            'rev' => 1,
            'debug' => false,

            'elements' => [
                ['section', 'Block', 'Flow', 'Common'],
                ['nav', 'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside', 'Block', 'Flow', 'Common'],
                ['header', 'Block', 'Flow', 'Common'],
                ['footer', 'Block', 'Flow', 'Common'],

                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],

                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],

                [
                    'video',
                    'Block',
                    'Optional: (source, Flow) | (Flow, source) | Flow',
                    'Common',
                    [
                        'src' => 'URI',
                        'type' => 'Text',
                        'width' => 'Length',
                        'height' => 'Length',
                        'poster' => 'URI',
                        'preload' => 'Enum#auto,metadata,none',
                        'controls' => 'Bool',
                    ]
                ],

                [
                    'source',
                    'Block',
                    'Flow',
                    'Common',
                    [
                        'src' => 'URI',
                        'type' => 'Text',
                    ]
                ],

                ['s', 'Inline', 'Inline', 'Common'],
                ['var', 'Inline', 'Inline', 'Common'],
                ['sub', 'Inline', 'Inline', 'Common'],
                ['sup', 'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr', 'Inline', 'Empty', 'Core'],

                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],

            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],

                ['table', 'height', 'Text'],
                ['table', 'width', 'Text'],
                ['table', 'border', 'Text'],
                ['table', 'cellpadding', 'Text'],
                ['table', 'cellspacing', 'Text'],

                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],

                ['td', 'border', 'Text'],
                ['td', 'width', 'Text'],
                ['td', 'height', 'Text'],
                ['td', 'colspan', 'Text'],
                ['td', 'rowspan', 'Text'],

                ['th', 'border', 'Text'],
                ['th', 'width', 'Text'],
                ['th', 'height', 'Text'],
                ['th', 'colspan', 'Text'],
                ['th', 'rowspan', 'Text'],
            ],
        ],

        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
        ],

        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],
    ],
];