<?php
/**
* @package Helix3 Framework
* @author JoomShaper http://www.joomshaper.com
* @copyright Copyright (c) 2010 - 2017 JoomShaper
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
*/

defined('_JEXEC') or die('Restricted Access');

$layout_path  = JPATH_ROOT .'/plugins/system/helixultimate/layouts';
$data = $displayData;

$output ='';
$output .= '<' . $data['sematic'] . ' id="' . $data['id'] . '"' . $data['row_class'] . '>';

if ($data['componentArea'])
{
    if (!$data['pagebuilder'])
    {
        $output .= '<div class="container">';
        $output .= '<div class="container-inner">';
    }
}
else
{
    if (!$data['fluidrow'])
    {
        $output .= '<div class="container">';
        $output .= '<div class="container-inner">';
    }
}

$getLayout = new JLayoutFile('frontend.rows', $layout_path );
$output .= $getLayout->render($data);

if ($data['componentArea'])
{
    if (!$data['pagebuilder'])
    {
        $output .= '</div>';
        $output .= '</div>';
    }
}
else
{
    if (!$data['fluidrow'])
    {
        $output .= '</div>';
        $output .= '</div>';
    }
}

$output .= '</' . $data['sematic'] . '>';

echo $output;
