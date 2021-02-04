<?php
/**
 * @package Helix_Ultimate_Framework
 * @author JoomShaper <support@joomshaper.com>
 * @copyright Copyright (c) 2010 - 2018 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */

Namespace HelixUltimate\Framework\Core;

defined('_JEXEC') or die();

use HelixUltimate\Framework\Platform\Helper;
use HelixUltimate\Framework\System\HelixCache;
use HelixUltimate\Framework\System\JoomlaBridge;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Initiator class for viewing
 * template.
 *
 * @since	1.0.0
 */
class HelixUltimate
{
	/**
	 * Template params.
	 *
	 * @var		object		$params		The helix params.
	 * @since	1.0.0
	 */
	public $params;

	/**
	 * The document object
	 *
	 * @var		JDocument
	 * @since	1.0.0
	 */
	private $doc;

	/**
	 * Joomla! app instance.
	 *
	 * @var		CMSApplication		$app	The CMS application instance.
	 * @since	1.0.0
	 */
	public $app;

	/**
	 * Input instance
	 *
	 * @var		JInput
	 * @since	1.0.0
	 */
	public $input;

	/**
	 * Get active template.
	 *
	 * @var		object	$template
	 * @since	1.0.0
	 */
	public $template;

	/**
	 * Template folder url.
	 *
	 * @var		string
	 * @since	1.0.0
	 */
	public $template_folder_url;

	/**
	 * In positions
	 *
	 * @var		array
	 * @since	1.0.0
	 */
	private $in_positions = array();

	/**
	 * Load feature
	 *
	 * @var		array
	 * @since	1.0.0
	 */
	public $loadFeature = array();

	/**
	 * Constructor function.
	 *
	 * @since	1.0.0
	 */
	public function __construct()
	{
		$this->app      = Factory::getApplication();
		$this->input    = $this->app->input;
		$this->doc      = Factory::getDocument();

		/**
		 * Load template data from cache or database
		 * for initializing the template
		 */
		$this->template = Helper::loadTemplateData();
		$this->params   = $this->template->params;
		$this->get_template_uri();
	}

	/**
	 * Generate body class.
	 *
	 * @param	string	$class	Body class.
	 *
	 * @return	string
	 * @since	1.0.0
	 */
	public function bodyClass($class = '')
	{
		$menu = $this->app->getMenu()->getActive();
		$menuParams = empty($menu) ? new Registry : $menu->getParams();


		$stickyHeader 	= $this->params->get('sticky_header', 0) ? ' sticky-header' : '';
		$stickyHeader 	= $this->params->get('sticky_header_md', 0) ? $stickyHeader . ' sticky-header-md' : $stickyHeader;
		$stickyHeader 	= $this->params->get('sticky_header_sm', 0) ? $stickyHeader . ' sticky-header-sm' : $stickyHeader;

		$bodyClass       = 'site hu ' . htmlspecialchars(str_replace('_', '-', $this->input->get('option', '', 'STRING')));
		$bodyClass      .= ' view-' . htmlspecialchars($this->input->get('view', '', 'STRING'));
		$bodyClass      .= ' layout-' . htmlspecialchars($this->input->get('layout', 'default', 'STRING'));
		$bodyClass      .= ' task-' . htmlspecialchars($this->input->get('task', 'none', 'STRING'));
		$bodyClass      .= ' itemid-' . (int) $this->input->get('Itemid', '', 'INT');
		$bodyClass      .= ($this->doc->language) ? ' ' . $this->doc->language : '';
		$bodyClass      .= ($this->doc->direction) ? ' ' . $this->doc->direction : '';
		$bodyClass 		.= $stickyHeader;
		$bodyClass      .= ($this->params->get('boxed_layout', 0)) ? ' layout-boxed' : ' layout-fluid';
		$bodyClass      .= ' offcanvas-init offcanvs-position-' . $this->params->get('offcanvas_position', 'right');

		if (isset($menu) && $menu)
		{
			if ($menuParams->get('pageclass_sfx'))
			{
				$bodyClass .= ' ' . $menuParams->get('pageclass_sfx');
			}
		}

		$bodyClass .= (!empty($class)) ? ' ' . $class : '';

		return $bodyClass;
	}

	public function googleAnalytics()
	{
		$code = $this->params->get('ga_code', null);
		$method = $this->params->get('ga_tracking_method', 'gst');

		if (!empty($code))
		{
			$code = preg_replace('#\s+#', '', $code);
		}

		if ($method === 'gst')
		{
			$script = "
			<!-- Global site tag (gtag.js) - Google Analytics -->
			<script async src='https://www.googletagmanager.com/gtag/js?id={$code}'></script>
			<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag('js', new Date());

				gtag('config', '{$code}');
			</script>
			";
		}
		elseif ($method === 'ua')
		{
			$script = "
			<script>
				(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

				ga('create', '{$code}', 'auto');
				ga('send', 'pageview');
			</script>
			";
		}

		return $script;
	}

	/**
	 * Config header of the template.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function head()
	{
		$view 	= $this->input->get('view', '', 'STRING');
		$layout = $this->input->get('layout', 'default', 'STRING');

		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('bootstrap.framework');

		unset($this->doc->_scripts[Uri::base(true) . '/media/jui/js/bootstrap.min.js']);
		unset($this->doc->_scripts[Uri::base(true) . '/media/jui/js/bootstrap-tooltip-extended.min.js']);

		$webfonts = array();

		if ($this->params->get('enable_body_font'))
		{
			$webfonts['body'] = $this->params->get('body_font');
		}

		if ($this->params->get('enable_h1_font'))
		{
			$webfonts['h1'] = $this->params->get('h1_font');
		}

		if ($this->params->get('enable_h2_font'))
		{
			$webfonts['h2'] = $this->params->get('h2_font');
		}

		if ($this->params->get('enable_h3_font'))
		{
			$webfonts['h3'] = $this->params->get('h3_font');
		}

		if ($this->params->get('enable_h4_font'))
		{
			$webfonts['h4'] = $this->params->get('h4_font');
		}

		if ($this->params->get('enable_h5_font'))
		{
			$webfonts['h5'] = $this->params->get('h5_font');
		}

		if ($this->params->get('enable_h6_font'))
		{
			$webfonts['h6'] = $this->params->get('h6_font');
		}

		if ($this->params->get('enable_navigation_font'))
		{
			$webfonts['.sp-megamenu-parent > li > a, .sp-megamenu-parent > li > span, .sp-megamenu-parent .sp-dropdown li.sp-menu-item > a'] = $this->params->get('navigation_font');
		}

		if ($this->params->get('enable_custom_font') && $this->params->get('custom_font_selectors'))
		{
			$webfonts[$this->params->get('custom_font_selectors')] = $this->params->get('custom_font');
		}

		// Favicon
		if ($favicon = $this->params->get('favicon'))
		{
			$this->doc->addFavicon(Uri::base(true) . '/' . $favicon);
		}
		else
		{
			$this->doc->addFavicon($this->template_folder_url . '/images/favicon.ico');
		}

		$this->addGoogleFont($webfonts);

		$this->doc->addScriptdeclaration('template="' . $this->template->template . '";');
		$this->doc->setGenerator('Helix Ultimate - The Most Popular Joomla! Template Framework.');

		if (JoomlaBridge::getVersion('major') < 4)
		{
			echo '<jdoc:include type="head" />';
		}
		else
		{
			echo '<jdoc:include type="metas" />';
			echo '<jdoc:include type="styles" />';
		}

		$this->add_css('bootstrap.min.css');

		if ($view === 'form' && $layout === 'edit')
		{
			$this->doc->addStylesheet(Uri::root(true) . '/plugins/system/helixultimate/assets/css/frontend-edit.css');
		}

		$bsBundleJSPath = JPATH_ROOT . '/templates/' . $this->template->template . '/js/bootstrap.bundle.min.js';
		$bsJsPath = JPATH_ROOT . '/templates/' . $this->template->template . '/js/bootstrap.min.js';

		if (\file_exists($bsBundleJSPath))
		{
			$this->add_js('bootstrap.bundle.min.js');
		}
		elseif (\file_exists($bsJsPath))
		{
			$this->add_js('popper.min.js, bootstrap.min.js');
		}
	}

	/**
	 * Add css files at header.
	 *
	 * @param	string		$css_files	Css files seperated by comma.
	 * @param	array		$options	Stylesheet options
	 * @param	array		$attribs	Tag attributes
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function add_css($css_files = '', $options = array(), $attribs = array())
	{
		$files = array(
			'resource' => $css_files,
			'options'  => $options,
			'attribs'  => $attribs
		);

		$this->put_css_js_file($files, 'css');
	}

	/**
	 * Add javascript file to head.
	 *
	 * @param	string	$js_files	Javascript files separated by comma.
	 * @param	array	$options	Script options.
	 * @param	array	$attribs	Script tag attributes.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function add_js($js_files = '', $options = array(), $attribs = array())
	{
		$files = array(
			'resource' => $js_files,
			'options'  => $options,
			'attribs'  => $attribs
		);

		$this->put_css_js_file($files, 'js');
	}

	/**
	 * Put css and js files into header.
	 *
	 * @param	array	$files		The files array containing the file paths, doc options, and tag attributes.
	 * @param	string	$folder	Type of the file to add into header. @availables are (js, css)
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	private function put_css_js_file($files = array(), $folder = '')
	{
		$asset_path = JPATH_THEMES . "/{$this->template->template}/{$folder}/";
		$file_list = explode(',', $files['resource']);

		foreach ($file_list as $file)
		{
			if (empty($file))
			{
				continue;
			}

			$file = trim($file);
			$file_path = $asset_path . $file;

			if (File::exists($file_path))
			{
				$file_url = Uri::base(true) . '/templates/' . $this->template->template . '/' . $folder . '/' . $file;
			}
			elseif (File::exists($file))
			{
				$file_url = $file;
			}
			else
			{
				continue;
			}

			if ($folder === 'js')
			{
				$this->doc->addScript($file_url, $files['options'], $files['attribs']);
			}
			else
			{
				$this->doc->addStyleSheet($file_url, $files['options'], $files['attribs']);
			}
		}
	}

	/**
	 * Get template URI.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	private function get_template_uri()
	{
		$this->template_folder_url = Uri::base(true) . '/templates/' . $this->template->template;
	}

	/**
	 * Include features.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	private function include_features()
	{
		$folder_path = JPATH_THEMES . '/' . $this->template->template . '/features';

		if (Folder::exists($folder_path))
		{
			$files = Folder::files($folder_path, '.php');

			if (!empty($files))
			{
				foreach ($files as $key => $file)
				{
					include_once $folder_path . '/' . $file;

					$file_name = File::stripExt($file);
					$class = 'HelixUltimateFeature' . ucfirst($file_name);
					$feature_obj = new $class($this->params);
					$position = $feature_obj->position;
					$load_pos = (isset($feature_obj->load_pos) && $feature_obj->load_pos) ? $feature_obj->load_pos : '';

					$this->in_positions[] = $position;

					if (!empty($position))
					{
						$this->loadFeature[$position][$key]['feature'] = $feature_obj->renderFeature();
						$this->loadFeature[$position][$key]['load_pos'] = $load_pos;
					}
				}
			}
		}
	}

	/**
	 * Render Layout
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function render_layout()
	{
		$this->add_css('custom.css');
		$this->add_js('custom.js');
		$this->include_features();

		$layout = ($this->params->get('layout')) ? $this->params->get('layout') : [];

		if (!empty($layout))
		{
			$rows   = json_decode($layout);
		}
		else
		{
			$layout_file = JPATH_SITE . '/templates/' . $this->template->template . '/options.json';

			if (!File::exists($layout_file))
			{
				die('Default Layout file is not exists! Please goto to template manager and create a new layout first.');
			}

			// $layout_data = json_decode(File::read($layout_file));
			$layout_data = json_decode(file_get_contents($layout_file));
			$rows = json_decode($layout_data->layout);
		}

		$output = $this->get_recursive_layout($rows);	

		echo $output;
	}

	private function get_recursive_layout($rows = array())
	{
		if (empty($rows) || !is_array($rows))
		{
			return;
		}

		$option      = $this->app->input->getCmd('option', '');
		$view        = $this->app->input->getCmd('view', '');
		$pagebuilder = false;
		$output = '';

		if ($option === 'com_sppagebuilder')
		{
			$pagebuilder = true;
		}

		$themepath      = JPATH_THEMES . '/' . $this->template->template;
		$carea_file     = $themepath . '/html/layouts/helixultimate/frontend/conponentarea.php';
		$module_file    = $themepath . '/html/layouts/helixultimate/frontend/modules.php';
		$lyt_thm_path   = $themepath . '/html/layouts/helixultimate/';

		$layout_path_carea  = (file_exists($carea_file)) ? $lyt_thm_path : JPATH_ROOT . '/plugins/system/helixultimate/layouts';
		$layout_path_module = (file_exists($module_file)) ? $lyt_thm_path : JPATH_ROOT . '/plugins/system/helixultimate/layouts';

		foreach ($rows as $key => $row)
		{
			$modified_row = $this->get_current_row($row);
			$columns = $modified_row->attr;

			if ($columns)
			{
				$componentArea = false;

				if (isset($modified_row->has_component) && $modified_row->has_component)
				{
					$componentArea = true;
				}

				$fluidrow = false;

				if (isset($modified_row->settings->fluidrow) && $modified_row->settings->fluidrow)
				{
					$fluidrow = $modified_row->settings->fluidrow;
				}

				$id = (isset($modified_row->settings->name) && $modified_row->settings->name) ? 'sp-' . \JFilterOutput::stringURLSafe($modified_row->settings->name) : 'sp-section-' . ($key + 1);
				$row_class = $this->build_row_class($modified_row->settings);
				$this->add_row_styles($modified_row->settings, $id);
				$sematic = (isset($modified_row->settings->name) && $modified_row->settings->name) ? strtolower($modified_row->settings->name) : 'section';

				switch ($sematic)
				{
					case "header":
						$sematic = 'header';
						break;

					case "footer":
						$sematic = 'footer';
						break;

					default:
						$sematic = 'section';
						break;
				}

				$data = array(
					'sematic' 			=> $sematic,
					'id' 				=> $id,
					'row_class' 		=> $row_class,
					'componentArea' 	=> $componentArea,
					'pagebuilder' 		=> $pagebuilder,
					'fluidrow' 			=> $fluidrow,
					'rowColumns' 		=> $columns,
					'loadFeature'       => $this->loadFeature
				);

				$layout_path  = JPATH_ROOT . '/plugins/system/helixultimate/layouts';
				$getLayout = new \JLayoutFile('frontend.generate', $layout_path);
				$output .= $getLayout->render($data);
			}
		}

		return $output;
	}

	/**
	 * Get current row
	 *
	 * @param	array	$row	layout rows
	 *
	 * @return	array	Updated rows.
	 * @since	1.0.0
	 */
	private function get_current_row($row)
	{
		// Absence span
		$inactive_col   = 0;
		$has_component  = false;

		foreach ($row->attr as $key => &$column)
		{
			$column->settings->disable_modules = isset($column->settings->name) ? $this->disable_details_page_modules($column->settings->name) : false;

			if (!$column->settings->column_type)
			{
				if (!$this->count_modules($column->settings->name))
				{
					$inactive_col += $column->settings->grid_size;
					unset($row->attr[$key]);
				}

				if ($column->settings->disable_modules && $this->count_modules($column->settings->name))
				{
					$inactive_col += $column->settings->grid_size;
					unset($row->attr[$key]);
				}
			}
			else
			{
				$row->has_component = true;
				$has_component = true;
			}
		}

		foreach ($row->attr as &$column)
		{
			$options = $column->settings;
			$col_grid_size = $options->grid_size;

			if (!$has_component && end($row->attr) === $column)
			{
				$col_grid_size = $col_grid_size + $inactive_col;
			}

			if ($options->column_type)
			{
				$col_grid_size = $col_grid_size + $inactive_col;
				$className = 'col-lg-' . $col_grid_size;
			}
			else
			{
				if (isset($options->lg_col) && $options->lg_col)
				{
					$className = $className . ' col-lg-' . $options->lg_col;
				}
				else
				{
					$className = 'col-lg-' . $col_grid_size;
				}
			}

			if (isset($options->xl_col) && $options->xl_col)
			{
				$className = $className . ' col-xl-' . $options->xl_col;
			}

			if (isset($options->md_col) && $options->md_col)
			{
				$className = 'col-md-' . $options->md_col . ' ' . $className;
			}

			if (isset($options->sm_col) && $options->sm_col)
			{
				$className = 'col-sm-' . $options->sm_col . ' ' . $className;
			}

			if (isset($options->xs_col) && $options->xs_col)
			{
				$className = 'col-' . $options->xs_col . ' ' . $className;
			}

			$device_class = $this->get_device_class($options);
			$column->settings->className = $className . ' ' . $device_class;
		}

		return $row;
	}

	/**
	 * Add row styles.
	 *
	 * @param	object	$options	Row style options.
	 * @param	integer	$id			Row ID.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	private function add_row_styles($options, $id)
	{
		$row_css = '';

		if (isset($options->background_image) && $options->background_image)
		{
			$row_css .= 'background-image:url("' . Uri::base(true) . '/' . $options->background_image . '");';

			if (isset($options->background_repeat) && $options->background_repeat)
			{
				$row_css .= 'background-repeat:' . $options->background_repeat . ';';
			}

			if (isset($options->background_size) && $options->background_size)
			{
				$row_css .= 'background-size:' . $options->background_size . ';';
			}

			if (isset($options->background_attachment) && $options->background_attachment)
			{
				$row_css .= 'background-attachment:' . $options->background_attachment . ';';
			}

			if (isset($options->background_position) && $options->background_position)
			{
				$row_css .= 'background-position:' . $options->background_position . ';';
			}
		}

		if (isset($options->background_color) && $options->background_color)
		{
			$row_css .= 'background-color:' . $options->background_color . ';';
		}

		if (isset($options->color) && $options->color)
		{
			$row_css .= 'color:' . $options->color . ';';
		}

		if (isset($options->padding) && $options->padding)
		{
			$row_css .= 'padding:' . $options->padding . ';';
		}

		if (isset($options->margin) && $options->margin)
		{
			$row_css .= 'margin:' . $options->margin . ';';
		}

		if ($row_css)
		{
			$this->doc->addStyledeclaration('#' . $id . '{ ' . $row_css . ' }');
		}

		if (isset($options->link_color) && $options->link_color)
		{
			$this->doc->addStyledeclaration('#' . $id . ' a{color:' . $options->link_color . ';}');
		}

		if (isset($options->link_hover_color) && $options->link_hover_color)
		{
			$this->doc->addStyledeclaration('#' . $id . ' a:hover{color:' . $options->link_hover_color . ';}');
		}
	}

	/**
	 * Generate the class of the row.
	 *
	 * @param	object	$options	Row options.
	 *
	 * @return	string	The classes of the row.
	 * @since	1.0.0
	 */
	private function build_row_class($options)
	{
		$row_class = '';

		if (isset($options->custom_class) && $options->custom_class)
		{
			$row_class .= $options->custom_class;
		}

		$device_class = $this->get_device_class($options);

		if ($device_class)
		{
			$row_class .= ' ' . $device_class;
		}

		if ($row_class)
		{
			$row_class = 'class="' . $row_class . '"';
		}

		return $row_class;
	}

	/**
	 * Get device class for responsiveness.
	 *
	 * @param	object 	$options	Options object.
	 *
	 * @return	string	Device classes.
	 * @since	1.0.0
	 */
	private function get_device_class($options)
	{
		$device_class = '';

		if (isset($options->hide_on_phone) && $options->hide_on_phone)
		{
			$device_class = 'd-none d-sm-block';
		}

		if (isset($options->hide_on_large_phone) && $options->hide_on_large_phone)
		{
			$device_class = $this->reshape_device_class('sm', $device_class);
			$device_class .= ' d-sm-none d-md-block';
		}

		if (isset($options->hide_on_tablet) && $options->hide_on_tablet)
		{
			$device_class = $this->reshape_device_class('md', $device_class);
			$device_class .= ' d-md-none d-lg-block';
		}

		if (isset($options->hide_on_small_desktop) && $options->hide_on_small_desktop)
		{
			$device_class = $this->reshape_device_class('lg', $device_class);
			$device_class .= ' d-lg-none d-xl-block';
		}

		if (isset($options->hide_on_desktop) && $options->hide_on_desktop)
		{
			$device_class = $this->reshape_device_class('xl', $device_class);
			$device_class .= ' d-xl-none';
		}

		return $device_class;
	}

	/**
	 * Reshape the device classes for responsiveness.
	 *
	 * @param	string	$device		The device indicator.
	 * @param	string	$class		The existing class.
	 *
	 * @return	string	The updated class
	 * @since	1.0.0
	 */
	private function reshape_device_class($device = '', $class = '')
	{
		$search = 'd-' . $device . '-block';
		$class = str_replace($search, '', $class);
		$class = trim($class, ' ');

		return $class;
	}

	/**
	 * Count the number of modules of a position.
	 *
	 * @param	string	$position	Module position.
	 *
	 * @return	integer	The number of modules.
	 * @since	1.0.0
	 */
	public function count_modules($position)
	{
		return ($this->doc->countModules($position) || $this->has_feature($position));
	}

	/**
	 * Disable module only from article page.
	 *
	 * @param	string	$position	Module position.
	 *
	 * @return	boolean
	 * @since	1.0.0
	 */
	private function disable_details_page_modules( $position )
	{
		$article_and_disable = ($this->app->input->get('view') === 'article' && $this->params->get('disable_module'));
		$match_positions = $position === 'left' || $position === 'right';

		return ($article_and_disable && $match_positions);
	}

	/**
	 * If the position has feature.
	 *
	 * @param	string	$position	The module position.
	 *
	 * @return	boolean	True on success, false otherwise.
	 * @since	1.0.0
	 */
	private function has_feature($position)
	{
		if (in_array($position, $this->in_positions))
		{
			return true;
		}

		return false;
	}

	/**
	 * Perform after body expressions.
	 *
	 * @return	string
	 * @since	1.0.0
	 */
	public function after_body()
	{
		if ($this->params->get('compress_css'))
		{
			$this->compress_css();
		}

		if ($this->params->get('compress_js'))
		{
			$this->compress_js($this->params->get('exclude_js'));
		}

		if ($before_body = $this->params->get('before_body'))
		{
			echo $before_body . "\n";
		}
	}

	/**
	 * Init all the SCSS.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function scssInit()
	{
		include_once __DIR__ . '/Classes/scss/Base/Range.php';
		include_once __DIR__ . '/Classes/scss/Block.php';
		include_once __DIR__ . '/Classes/scss/Colors.php';
		include_once __DIR__ . '/Classes/scss/Compiler.php';
		include_once __DIR__ . '/Classes/scss/Compiler/Environment.php';
		include_once __DIR__ . '/Classes/scss/Exception/CompilerException.php';
		include_once __DIR__ . '/Classes/scss/Exception/ParserException.php';
		include_once __DIR__ . '/Classes/scss/Exception/ServerException.php';
		include_once __DIR__ . '/Classes/scss/Formatter.php';
		include_once __DIR__ . '/Classes/scss/Formatter/Compact.php';
		include_once __DIR__ . '/Classes/scss/Formatter/Compressed.php';
		include_once __DIR__ . '/Classes/scss/Formatter/Crunched.php';
		include_once __DIR__ . '/Classes/scss/Formatter/Debug.php';
		include_once __DIR__ . '/Classes/scss/Formatter/Expanded.php';
		include_once __DIR__ . '/Classes/scss/Formatter/Nested.php';
		include_once __DIR__ . '/Classes/scss/Formatter/OutputBlock.php';
		include_once __DIR__ . '/Classes/scss/Node.php';
		include_once __DIR__ . '/Classes/scss/Node/Number.php';
		include_once __DIR__ . '/Classes/scss/Parser.php';
		include_once __DIR__ . '/Classes/scss/Type.php';
		include_once __DIR__ . '/Classes/scss/Util.php';
		include_once __DIR__ . '/Classes/scss/Version.php';

		return new \Leafo\ScssPhp\Compiler;
	}

	/**
	 * Add scss file with options.
	 *
	 * @param	string	$scss			The scss file name.
	 * @param	array	$vars			The variables array.
	 * @param	string	$css			The css file name.
	 * @param	boolean	$forceCompile	Compile the scss to css by force
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function add_scss($scss, $vars = array(), $css = '', $forceCompile = false)
	{
		$scss = File::stripExt($scss);

		if (!empty($css))
		{
			$css = File::stripExt($css) . '.css';
		}
		else
		{
			$css = $scss . '.css';
		}

		if ($this->params->get('scssoption'))
		{
			$needsCompile = $this->needScssCompile($scss, $vars);

			if ($forceCompile || $needsCompile)
			{
				$scssInit = $this->scssInit();
				$template = Helper::loadTemplateData()->template;
				$scss_path = JPATH_THEMES . '/' . $template . '/scss';
				$css_path = JPATH_THEMES . '/' . $template . '/css';

				if (file_exists($scss_path . '/' . $scss . '.scss'))
				{
					$out = $css_path . '/' . $css;
					$scssInit->setFormatter('Leafo\ScssPhp\Formatter\Expanded');
					$scssInit->setImportPaths($scss_path);

					if (!empty($vars))
					{
						$scssInit->setVariables($vars);
					}

					$compiledCss = $scssInit->compile('@import "' . $scss . '.scss"');
					File::write($out, $compiledCss);

					$cache_path = \JPATH_CACHE . '/com_templates/templates/' . $template . '/' . $scss . '.scss.cache';
					$scssCache = array();
					$scssCache['imports'] = $scssInit->getParsedFiles();
					$scssCache['vars'] = $scssInit->getVariables();
					File::write($cache_path, json_encode($scssCache));
				}
			}
		}

		$this->add_css($css);
	}

	/**
	 * If it is needed to compile the scss.
	 *
	 * @param	string	$scss		The scss file name.
	 * @param	array	$vars	Scss variables.
	 *
	 * @return	boolean
	 * @since	1.0.0
	 */
	public function needScssCompile($scss, $vars = array())
	{
		$cache_path = \JPATH_CACHE . '/com_templates/templates/' . $this->template->template . '/' . $scss . '.scss.cache';

		if (file_exists($cache_path))
		{
			$cache_file = json_decode(file_get_contents($cache_path));
			$imports = (isset($cache_file->imports) && $cache_file->imports) ? $cache_file->imports : array();
			$cached_vars = (isset($cache_file->vars) && $cache_file->vars) ? (array) $cache_file->vars : array();

			if (array_diff_assoc($vars, $cached_vars))
			{
				return true;
			}

			if ($imports)
			{
				foreach ($imports as $import => $mtime)
				{
					if (file_exists($import))
					{
						$existModificationTime = filemtime($import);

						if ($existModificationTime != $mtime)
						{
							return true;
						}
					}
					else
					{
						return true;
					}
				}
			}
			else
			{
				return true;
			}
		}
		else
		{
			return true;
		}

		return false;
	}

	/**
	 * Add google fonts.
	 *
	 * @param	array	$fonts	Google fonts.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function addGoogleFont($fonts)
	{
		// $doc = Factory::getDocument();

		$systemFonts = array(
			'Arial',
			'Tahoma',
			'Verdana',
			'Helvetica',
			'Times New Roman',
			'Trebuchet MS',
			'Georgia'
		);

		if (is_array($fonts))
		{
			foreach ($fonts as $key => $font)
			{
				$font = json_decode($font);

				if (!in_array($font->fontFamily, $systemFonts))
				{
					$fontUrl = '//fonts.googleapis.com/css?family=' . $font->fontFamily . ':100,100i,300,300i,400,400i,500,500i,700,700i,900,900i';

					if (isset($font->fontSubset) && $font->fontSubset)
					{
						$fontUrl .= '&amp;subset=' . $font->fontSubset;
					}

					$fontUrl .= '&amp;display=swap';

					$this->doc->addStylesheet($fontUrl, ['version' => 'auto'], ['media' => 'none', 'onload' => 'media="all"']);
				}

				$fontCSS = $key . "{";
				$fontCSS .= "font-family: '" . $font->fontFamily . "', sans-serif;";

				if (isset($font->fontSize) && $font->fontSize)
				{
					$fontCSS .= 'font-size: ' . $font->fontSize . 'px;';
				}

				if (isset($font->fontWeight) && $font->fontWeight)
				{
					$fontCSS .= 'font-weight: ' . $font->fontWeight . ';';
				}

				if (isset($font->fontStyle) && $font->fontStyle)
				{
					$fontCSS .= 'font-style: ' . $font->fontStyle . ';';
				}

				if (!empty($font->fontColor))
				{
					$fontCSS .= 'color: ' . $font->fontColor . ';';
				}

				if (!empty($font->fontLineHeight))
				{
					$fontCSS .= 'line-height: ' . $font->fontLineHeight . ';';
				}

				if (!empty($font->fontLetterSpacing))
				{
					$fontCSS .= 'letter-spacing: ' . $font->fontLetterSpacing . ';';
				}

				if (!empty($font->textDecoration))
				{
					$fontCSS .= 'text-decoration: ' . $font->textDecoration . ';';
				}

				if (!empty($font->textAlign))
				{
					$fontCSS .= 'text-align: ' . $font->textAlign . ';';
				}

				$fontCSS .= "}\n";

				if (isset($font->fontSize_sm) && $font->fontSize_sm)
				{
					$fontCSS .= '@media (min-width:768px) and (max-width:991px){';
					$fontCSS .= $key . "{";
					$fontCSS .= 'font-size: ' . $font->fontSize_sm . 'px;';
					$fontCSS .= "}\n}\n";
				}

				if (isset($font->fontSize_xs) && $font->fontSize_xs)
				{
					$fontCSS .= '@media (max-width:767px){';
					$fontCSS .= $key . "{";
					$fontCSS .= 'font-size: ' . $font->fontSize_xs . 'px;';
					$fontCSS .= "}\n}\n";
				}

				$this->doc->addStyledeclaration($fontCSS);
			}
		}
	}

	/**
	 * Exclude js files and return the other js.
	 *
	 * @param	string	$key		The key
	 * @param	string	$excludes	The files to excludes with comma seperated.
	 *
	 * @return	boolean
	 * @since	1.0.0
	 */
	private function exclude_js($key, $excludes)
	{
		$match = false;

		if ($excludes)
		{
			$excludes = explode(',', $excludes);

			foreach ($excludes as $exclude)
			{
				if (File::getName($key) == trim($exclude))
				{
					$match = true;
				}
			}
		}

		return $match;
	}

	/**
	 * Check if the contents of the assets are changed.
	 * If the contents are changed then the filesize must be changed.
	 *
	 * @param	string	$cachedFile		File path
	 * @param	string	$currentContent	The contents
	 *
	 * @return	bool
	 * @since	2.0.0
	 */
	private function contentsChanged($cachedFile, $currentContent)
	{
		$temp = tmpfile();
		fwrite($temp, $currentContent);
		fseek($temp, 0);
		$tempFileSize = filesize(stream_get_meta_data($temp)['uri']);
		fclose($temp);

		return filesize($cachedFile) !== $tempFileSize;
	}

	/**
	 * Check if the file is minified or not.
	 * This is getting the file contents and counting
	 * the number of lines in the file.
	 * If there is only one line that means this is a minified file.
	 * On the other hand, if the percentage of the ratio of the
	 * ($numberOfLines:$contentLength) is less then 1 that means there may
	 * have a few number of lines but that could be negligible.
	 *
	 * @param	string	$file	The file url
	 *
	 * @return	boolean	True if minified, false otherwise.
	 * @since	2.0.0
	 */
	private function isMinified($file)
	{
		$content = file_get_contents($file);
		$contentLength = strlen($content);
		$numberOfLines = preg_match_all("#[\r\n]#", $content);

		return ($numberOfLines === 1)
			|| (($numberOfLines * 100 / $contentLength) < 1);
	}

	/**
	 * Compress javascript.
	 *
	 * @param	string		$excludes	If any js to exclude from compressing.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function compress_js($excludes = '')
	{
		$app       = Factory::getApplication();
		$cachetime = $app->get('cachetime', 15);

		$all_scripts  = $this->doc->_scripts;
		$all_declared_scripts = $this->doc->_script;
		$cache_path   = JPATH_CACHE . '/com_templates/templates/' . $this->template->template;
		$scripts      = array();
		$root_url     = Uri::root(true);
		$minifiedCode = '';
		$md5sum       = '';
		$criticalCode = '';
		$criticalHash = '';
		$criticalRegex = "#(jquery.*)\.js$#";

		/**
		 * Version hashes are used here for maintaining
		 * the file versioning. If an asset file's content is changed then for caching,
		 * the browser cannot detect, if the file is changed or not.
		 * So it executes the cached one. But for rapid changing of styles as well as scripts
		 * we need to preview the changes.
		 *
		 * That's why we add this version control system. Where we add a version number hash
		 * with each js|css assets and if the content is changed then the hash also be changed
		 * so that the browser can detect, the file is changed and it brings the file
		 * from server, instead of browser cache.
		 *
		 * And don't worry. If the content of the file are same as before then the browser will
		 * bring the cached one.
		 */
		$versionHashes = array(
			'url' => 'auto',
			'declared' => 'auto',
			'critical' => 'auto'
		);

		// Check all local scripts
		foreach ($all_scripts as $key => $value)
		{
			$js_file = str_replace($root_url, JPATH_ROOT, $key);

			if (strpos($js_file, JPATH_ROOT) === false)
			{
				$js_file = JPATH_ROOT . $key;
			}

			if (File::exists($js_file))
			{
				if (!$this->exclude_js($key, $excludes))
				{
					$scripts[] = $key;

					if (preg_match($criticalRegex, $key))
					{
						$criticalHash .= md5($key);
					}
					else
					{
						$md5sum .= md5($key);
					}

					/**
					 * Check if an empty file is given for compression
					 */
					if (!strlen(file_get_contents($js_file)))
					{
						$compressed = "/* No content */";
					}
					else
					{
						/**
						 * If the file is already minified then skip it from re-minimization.
						 */
						if ($this->isMinified($js_file))
						{
							$compressed = file_get_contents($js_file);
						}
						else
						{
							$compressed = \JShrink\Minifier::minify(file_get_contents($js_file), array('flaggedComments' => false));
						}
					}

					// Add file name to compressed JS
					if (preg_match($criticalRegex, $key))
					{
						$criticalCode .= "/*------ " . File::getName($js_file) . " ------*/\n" . $compressed . "\n\n";
					}
					else
					{
						$minifiedCode .= "/*------ " . File::getName($js_file) . " ------*/\n" . $compressed . "\n\n";
					}

					// Remove scripts
					unset($this->doc->_scripts[$key]);
				}
			}
		}

		if ($criticalCode)
		{
			if (!Folder::exists($cache_path))
			{
				Folder::create($cache_path, 0755);
			}

			$fileMd5 = md5($criticalHash);
			$file = $cache_path . '/' . $fileMd5 . '.js';

			if (!File::exists($file))
			{
				File::write($file, $criticalCode);
			}
			else
			{
				/**
				 * Check if the current content of the JS
				 * is differ from the cached content.
				 * In such a situation override the cache file with the
				 * current changed content.
				 */
				if ($this->contentsChanged($file, $criticalCode)
					|| filesize($file) === 0
					|| ((filemtime($file) + $cachetime * 60) < time()))
				{
					File::write($file, $criticalCode);
				}
			}

			$versionHashes['critical'] = md5($criticalCode);

			/**
			 * Asynchronously load the non critical JavaScript
			 */
			$this->doc->addScript(
				Uri::base(true) . '/cache/com_templates/templates/' . $this->template->template . '/' . $fileMd5 . '.js',
				[
					'version' => $versionHashes['critical']
				]
			);
		}

		// Compress All scripts
		if ($minifiedCode)
		{
			if (!Folder::exists($cache_path))
			{
				Folder::create($cache_path, 0755);
			}

			$fileMd5 = md5($md5sum);
			$file = $cache_path . '/' . $fileMd5 . '.js';

			if (!File::exists($file))
			{
				File::write($file, $minifiedCode);
			}
			else
			{
				/**
				 * Check if the current content of the JS
				 * is differ from the cached content.
				 * In such a situation override the cache file with the
				 * current changed content.
				 */
				if ($this->contentsChanged($file, $minifiedCode)
					|| filesize($file) === 0
					|| ((filemtime($file) + $cachetime * 60) < time()))
				{
					File::write($file, $minifiedCode);
				}
			}

			$versionHashes['url'] = md5($minifiedCode);

			/**
			 * Asynchronously load the non critical JavaScript
			 */
			$this->doc->addScript(
				Uri::base(true) . '/cache/com_templates/templates/' . $this->template->template . '/' . $fileMd5 . '.js',
				[
					'version' => $versionHashes['url']
				],
				[
					'defer' => true
				]
			);
		}

		/**
		 * Make a javascript file at cache folder for declared scripts
		 * and make sure of deferring them.
		 */
		if (!empty($all_declared_scripts))
		{
			$declaredScriptHash = '';
			$scriptContent = '';

			foreach ($all_declared_scripts as $key => $script)
			{
				$declaredScriptHash .= md5($key);
				$scriptContent .= \JShrink\Minifier::minify($script, array('flaggedComments' => false));
				unset($this->doc->_script[$key]);
			}

			if (!empty($scriptContent))
			{
				if (!Folder::exists($cache_path))
				{
					Folder::create($cache_path, 0755);
				}

				$file = $cache_path . '/' . $declaredScriptHash . '.js';

				if (!File::exists($file))
				{
					File::write($file, $scriptContent);
				}
				else
				{
					/**
					 * Check if the current content of the JS
					 * is differ from the cached content.
					 * In such a situation override the cache file with the
					 * current changed content.
					 */
					if ($this->contentsChanged($file, $scriptContent)
						|| filesize($file) === 0
						|| ((filemtime($file) + $cachetime * 60) < time()))
					{
						File::write($file, $scriptContent);
					}
				}

				$versionHashes['declared'] = md5($scriptContent);

				/**
				 * Asynchronously load the non critical JavaScript
				 */
				$this->doc->addScript(
					Uri::base(true) . '/cache/com_templates/templates/' . $this->template->template . '/' . $declaredScriptHash . '.js',
					[
						'version' => $versionHashes['declared']
					],
					[
						'defer' => true
					]
				);
			}
		}

		return;
	}

	/**
	 * Get preloader of specific type
	 *
	 * @param	string	$type	Loader Type
	 *
	 * @return	string	Loader HTML string
	 * @since	2.0.0
	 */
	public function getPreloader($type)
	{
		$loader = array();

		switch ($type)
		{
			case 'circle':
				$loader[] = "<div class='sp-loader-circle'></div>";
			break;

			case 'bubble-loop':
				$loader[] = "<div class='sp-loader-bubble-loop'></div>";
			break;

			case 'wave-two':
				$loader[] = "<div class='wave-two-wrap'>";
				$loader[] = "<ul class='wave-two'>";
				$loader[] = str_repeat("<li></li>", 6);
				$loader[] = "</ul>";
				$loader[] = "</div>";
			break;

			case 'audio-wave':
				$loader[] = "<div class='sp-loader-audio-wave'></div>";
			break;

			case 'circle-two':
				$loader[] = "<div class='circle-two'><span></span></div>";
			break;

			case 'clock':
				$loader[] = "<div class='sp-loader-clock'></div>";
			break;

			case 'logo':
				$src = $this->params->get('logo_type') === 'image' ? Uri::root() . $this->params->get('logo_image') : null;

				$loader[] = "<div class='sp-loader-with-logo'>";
				$loader[] = "<div class='logo'>";
				$loader[] = $src ? "<img src='" . $src . "' />" : "Loading...";
				$loader[] = "</div>";
				$loader[] = "<div class='line' id='line-load'></div>";
				$loader[] = "</div>";
			break;

			default:
				$loader[] = "<div class='sp-preloader'></div>";
			break;
		}

		return implode("\n", $loader);
	}

	/**
	 * Get header style.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function getHeaderStyle()
	{
		$pre_header 	= $this->params->get('predefined_header');
		$header_style 	= $this->params->get('header_style');

		if (!$pre_header || !$header_style)
		{
			return;
		}

		$options = new \stdClass;
		$options->template 	= $this->template;
		$options->params 	= $this->params;
		$template 			= $options->template->template;

		$tmpl_file_location = JPATH_ROOT . '/templates/' . $template . '/headers';

		if (File::exists($tmpl_file_location . '/' . $header_style . '/header.php'))
		{
			$getLayout = new \JLayoutFile($header_style . '.header', $tmpl_file_location);

			return $getLayout->render($options);
		}
	}

	/**
	 * Minify CSS code.
	 *
	 * @param	string	$css_code	The css code snippet.
	 *
	 * @return	string	The minified code
	 * @since	1.0.0
	 */
	public function minifyCss($css_code)
	{
		// Remove comments
		$css_code = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css_code);

		// Remove space after colons
		$css_code = str_replace(': ', ':', $css_code);

		// Remove whitespace
		$css_code = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css_code);

		// Remove Empty Selectors without any properties
		$css_code = preg_replace('/(?:(?:[^\r\n{}]+)\s?{[\s]*})/', '', $css_code);

		// Remove Empty Media Selectors without any properties or selector
		$css_code = preg_replace('/@media\s?\((?:[^\r\n,{}]+)\s?{[\s]*}/', '', $css_code);

		return $css_code;
	}

	/**
	 * Compress css files.
	 *
	 * @return	void
	 * @since	1.0.0
	 */
	public function compress_css()
	{
		$app             = Factory::getApplication();
		$cachetime       = $app->get('cachetime', 15);
		$all_stylesheets = $this->doc->_styleSheets;
		$cache_path      = JPATH_CACHE . '/com_templates/templates/' . $this->template->template;
		$stylesheets     = array();
		$root_url        = Uri::root(true);
		$minifiedCode    = '';
		$md5sum          = '';

		$criticalCssRegex = "#(preset.*|font-awesome.*)\.css#";
		$criticalCssHash = '';
		$criticalCssCode = '';

		/**
		 * Version hashes are used here for maintaining
		 * the file versioning. If an asset file's content is changed then for caching,
		 * the browser cannot detect, if the file is changed or not.
		 * So it executes the cached one. But for rapid changing of styles as well as scripts
		 * we need to preview the changes.
		 *
		 * That's why we add this version control system. Where we add a version number hash
		 * with each js|css assets and if the content is changed then the hash also be changed
		 * so that the browser can detect, the file is changed and it brings the file
		 * from server, instead of browser cache.
		 *
		 * And don't worry. If the content of the file are same as before then the browser will
		 * bring the cached one.
		 */
		$versionHashes	= array(
			'critical' => 'auto',
			'lazy' => 'auto'
		);

		// Check all local stylesheets
		foreach ($all_stylesheets as $key => $value)
		{
			$css_file = str_replace($root_url, \JPATH_ROOT, $key);

			if (strpos($css_file, JPATH_ROOT) === false)
			{
				$css_file = JPATH_ROOT . $key;
			}

			$GLOBALS['absolute_url'] = $key;

			if (File::exists($css_file))
			{
				$stylesheets[] = $key;

				if (preg_match($criticalCssRegex, $key))
				{
					$criticalCssHash .= md5($key);
				}
				else
				{
					$md5sum .= md5($key);
				}

				$compressed = $this->minifyCss(file_get_contents($css_file));

				$fixUrl = preg_replace_callback('/url\(([^\):]*)\)/',
					function ($matches)
					{
						global $absolute_url;

						$url = str_replace(array('"', '\''), '', $matches[1]);

						if (preg_match('/\.(jpg|png|jpeg|mp4|gif|JPEG|JPG|PNG|GIF)$/', $url))
						{
							return "url('$url')";
						}

						$base = dirname($absolute_url);

						while (preg_match('/^\.\.\//', $url))
						{
							$base = dirname($base);
							$url  = substr($url, 3);
						}

						$url = $base . '/' . $url;

						return "url('$url')";
					},
					$compressed
				);

				// Add file name to compressed css
				if (preg_match($criticalCssRegex, $key))
				{
					$criticalCssCode .= "/*------ " . basename($css_file) . " ------*/\n" . $fixUrl . "\n\n";
				}
				else
				{
					$minifiedCode .= "/*------ " . basename($css_file) . " ------*/\n" . $fixUrl . "\n\n";
				}

				// Remove stylesheets
				unset($this->doc->_styleSheets[$key]);
			}
		}

		if ($criticalCssCode)
		{
			if (!Folder::exists($cache_path))
			{
				Folder::create($cache_path, 0755);
			}

			$file = $cache_path . '/' . md5($criticalCssHash) . '.css';

			if (!File::exists($file))
			{
				File::write($file, $criticalCssCode);
			}
			else
			{
				/**
				 * Check if the current content of the CSS
				 * is differ from the cached content.
				 * In such a situation override the cache file with the
				 * current changed content.
				 */
				if ($this->contentsChanged($file, $criticalCssCode)
					|| filesize($file) === 0
					|| ((filemtime($file) + $cachetime * 60) < time()))
				{
					File::write($file, $criticalCssCode);
				}
			}

			$versionHashes['critical'] = md5($criticalCssCode);

			/**
			 * Load template styles asynchronously
			 */
			$this->doc->addStylesheet(
				Uri::base(true) . '/cache/com_templates/templates/' . $this->template->template . '/' . md5($criticalCssHash) . '.css',
				['version' => $versionHashes['critical']]
			);
		}

		// Compress All stylesheets
		if ($minifiedCode)
		{
			if (!Folder::exists($cache_path))
			{
				Folder::create($cache_path, 0755);
			}

			$file = $cache_path . '/' . md5($md5sum) . '.css';

			if (!File::exists($file))
			{
				File::write($file, $minifiedCode);
			}
			else
			{
				/**
				 * Check if the current content of the CSS
				 * is differ from the cached content.
				 * In such a situation override the cache file with the
				 * current changed content.
				 */
				if ($this->contentsChanged($file, $minifiedCode)
					|| filesize($file) === 0
					|| ((filemtime($file) + $cachetime * 60) < time()))
				{
					File::write($file, $minifiedCode);
				}
			}

			$versionHashes['lazy'] = md5($minifiedCode);

			/**
			 * Load template styles asynchronously
			 */
			$this->doc->addStylesheet(
				Uri::base(true) . '/cache/com_templates/templates/' . $this->template->template . '/' . md5($md5sum) . '.css',
				['version' => $versionHashes['lazy']],
				[
					'media' => 'none',
					'onload' => "media='all'"
				]
			);
		}

		return;
	}

	/**
	 * Get related articles.
	 *
	 * @param	object 	$params		Article params.
	 *
	 * @return	array	Articles
	 * @since	1.0.0
	 */
	public static function getRelatedArticles($params)
	{
		$user   = Factory::getUser();
		$userId = $user->get('id');
		$guest  = $user->get('guest');
		$groups = $user->getAuthorisedViewLevels();
		$authorised = Access::getAuthorisedViewLevels($userId);

		$db = Factory::getDbo();
		$app = Factory::getApplication();
		$nullDate = $db->quote($db->getNullDate());
		$nowDate  = $db->quote(Factory::getDate()->toSql());
		$item_id = $params['item_id'];
		$maximum = isset($params['maximum']) ? (int) $params['maximum'] : 5;
		$maximum = $maximum < 1 ? 5 : $maximum;
		$catId = isset($params['catId']) ? (int) $params['catId'] : null;
		$tagids = [];

		if (isset($params['itemTags']) && count($params['itemTags']))
		{
			$itemTags = $params['itemTags'];

			foreach ($itemTags as $tag)
			{
				array_push($tagids, $tag->id);
			}
		}

		// Category filter
		$catItemIds = $tagItemIds = $itemIds = [];

		if ($catId !== null)
		{
			$catQuery = $db->getQuery(true)
				->clear()
				->select('id')
				->from($db->quoteName('#__content'))
				->where($db->quoteName('catid') . " = " . $catId)
				->setLimit($maximum + 1);

			$db->setQuery($catQuery);
			$catItemIds = $db->loadColumn();
		}

		// Tags filter
		if (is_array($tagids) && count($tagids))
		{
			$tagId = implode(',', ArrayHelper::toInteger($tagids));

			if ($tagId)
			{
				$subQuery = $db->getQuery(true)
					->clear()
					->select('DISTINCT content_item_id as id')
					->from($db->quoteName('#__contentitem_tag_map'))
					->where('tag_id IN (' . $tagId . ')')
					->where('type_alias = ' . $db->quote('com_content.article'));

				$db->setQuery($subQuery);
				$tagItemIds = $db->loadColumn();
			}
		}

		$itemIds = array_unique(array_merge($catItemIds, $tagItemIds));

		if (count($itemIds) < 1)
		{
			return [];
		}

		$itemIds = implode(',', ArrayHelper::toInteger($itemIds));
		$query = $db->getQuery(true);

		$query->clear()
			->select('a.*')
			->select('a.alias as slug')
			->from($db->quoteName('#__content', 'a'))
			->select($db->quoteName('b.alias', 'category_alias'))
			->select($db->quoteName('b.title', 'category'))
			->select($db->quoteName('b.access', 'category_access'))
			->select($db->quoteName('u.name', 'author'))
			->join('LEFT', $db->quoteName('#__categories', 'b') . ' ON (' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('b.id') . ')')
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.created_by') . ' = ' . $db->quoteName('u.id') . ')')
			->where($db->quoteName('a.access') . " IN (" . implode(',', $authorised) . ")")
			->where('a.id IN (' . $itemIds . ')')
			->where('a.id != ' . (int) $item_id);

		// Language filter
		if ($app->getLanguageFilter())
		{
			$query->where('a.language IN (' . $db->Quote(JFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
		}

		$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		$query->where($db->quoteName('a.state') . ' = ' . $db->quote(1));
		$query->order($db->quoteName('a.created') . ' DESC')
			->setLimit($maximum);

		$db->setQuery($query);
		$items = $db->loadObjectList();

		foreach ($items as &$item)
		{
			$item->slug    	= $item->id . ':' . $item->slug;
			$item->catslug 	= $item->catid . ':' . $item->category_alias;
			$item->params = ComponentHelper::getParams('com_content');
			$access = (isset($item->access) && $item->access) ? $item->access : true;

			if ($access)
			{
				$item->params->set('access-view', true);
			}
			else
			{
				if ($item->catid == 0 || $item->category_access === null)
				{
					$item->params->set('access-view', in_array($item->access, $groups));
				}
				else
				{
					$item->params->set('access-view', in_array($item->access, $groups) && in_array($item->category_access, $groups));
				}
			}
		}

		return $items;
	}
}
