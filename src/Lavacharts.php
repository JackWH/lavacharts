<?php

namespace Khill\Lavacharts;

use Khill\Lavacharts\Charts\Chart;
use Khill\Lavacharts\Charts\ChartFactory;
use Khill\Lavacharts\Dashboards\DashboardFactory;
use Khill\Lavacharts\Dashboards\Filters\Filter;
use Khill\Lavacharts\Dashboards\Filters\FilterFactory;
use Khill\Lavacharts\Dashboards\Wrappers\ChartWrapper;
use Khill\Lavacharts\Dashboards\Wrappers\ControlWrapper;
use Khill\Lavacharts\DataTables\DataFactory;
use Khill\Lavacharts\DataTables\DataTable;
use Khill\Lavacharts\DataTables\Formats\Format;
use Khill\Lavacharts\Exceptions\InvalidLabel;
use Khill\Lavacharts\Exceptions\InvalidRenderable;
use Khill\Lavacharts\Javascript\ScriptManager;
use Khill\Lavacharts\Support\Buffer;
use Khill\Lavacharts\Support\Contracts\Arrayable;
use Khill\Lavacharts\Support\Contracts\Jsonable;
use Khill\Lavacharts\Support\Contracts\Customizable;
use Khill\Lavacharts\Support\Html\HtmlFactory;
use Khill\Lavacharts\Support\Psr4Autoloader;
use Khill\Lavacharts\Support\Renderable;
use Khill\Lavacharts\Support\StringValue as Str;
use Khill\Lavacharts\Support\Traits\ArrayToJsonTrait as ArrayToJson;
use Khill\Lavacharts\Support\Traits\HasOptionsTrait as HasOptions;

/**
 * Lavacharts - A PHP wrapper library for the Google Chart API
 *
 *
 * @package   Khill\Lavacharts
 * @since     1.0.0
 * @author    Kevin Hill <kevinkhill@gmail.com>
 * @copyright (c) 2017, KHill Designs
 * @link      http://github.com/kevinkhill/lavacharts GitHub Repository Page
 * @link      http://lavacharts.com                   Official Docs Site
 * @license   http://opensource.org/licenses/MIT      MIT
 */
class Lavacharts implements Customizable, Jsonable, Arrayable
{
    use HasOptions, ArrayToJson;

    /**
     * Lavacharts version
     */
    const VERSION = '3.2.0';

    const BASE_LAVA_CLASSES = [
        'ChartWrapper',
        'ControlWrapper',
        'DataTable',
        'DataFactory',

    ];

    /**
     * Storage for all of the defined Renderables.
     *
     * @var \Khill\Lavacharts\Volcano
     */
    private $volcano;

    /**
     * Chart factory for creating new charts.
     *
     * @var \Khill\Lavacharts\Charts\ChartFactory
     */
    private $chartFactory;

    /**
     * Dashboard factory for creating dashboards.
     *
     * @var \Khill\Lavacharts\Dashboards\DashboardFactory
     */
    private $dashFactory;

    /**
     * Instance of the ScriptManager.
     *
     * @var ScriptManager
     */
    private $scriptManager;

    /**
     * Lavacharts constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->initOptions($options);

        if ( ! $this->usingComposer()) {
            require_once(__DIR__.'/Support/Psr4Autoloader.php');

            $loader = new Psr4Autoloader;
            $loader->register();
            $loader->addNamespace('Khill\Lavacharts', __DIR__);
        }

        $this->volcano       = new Volcano;
        $this->chartFactory  = new ChartFactory;
        $this->dashFactory   = new DashboardFactory;
        $this->scriptManager = new ScriptManager($this->options);

    }

    /**
     * Magic function to reduce repetitive coding and create aliases.
     *
     * @since  1.0.0
     * @param  string $method Name of method
     * @param  array  $args   Passed arguments
     * @throws \Khill\Lavacharts\Exceptions\InvalidLabel
     * @throws \Khill\Lavacharts\Exceptions\InvalidRenderable
     * @throws \Khill\Lavacharts\Exceptions\InvalidFunctionParam
     * @return mixed Returns Charts, Formats and Filters
     */
    public function __call($method, $args)
    {
        //Charts
        if (ChartFactory::isValidChart($method)) {
            if (isset($args[0]) === false) {
                throw new InvalidLabel;
            }

            if ($this->exists($method, $args[0])) {
                $label = Str::verify($args[0]);

                return $this->volcano->get($method, $label);
            } else {
                $chart = $this->chartFactory->make($method, $args);

                return $this->volcano->store($chart);
            }
        }

        //Filters
        if (Str::endsWith($method, 'Filter')) {
            $options = isset($args[1]) ? $args[1] : [];

            return FilterFactory::create($method, $args[0], $options);
        }

        //Formats
        if (Str::endsWith($method, 'Format')) {
            $options = isset($args[0]) ? $args[0] : [];

            return Format::create($method, $options);
        }

        throw new \BadMethodCallException(
            sprintf('Unknown method "%s" in "%s".', $method, get_class())
        );
    }

    /**
     * Run the library and get the resulting scripts.
     *
     *
     * This method will create a <script> for the lava.js module along with
     * one additional <script> per chart & dashboard being rendered.
     *
     * @since 3.2.0
     * @return string HTML script elements
     */
    public function flow()
    {
        return $this->renderAll();

        //@TODO This is the goal :)
//        return new ScriptManager($this->options, json_encode($this));
    }

    /**
     * Convert the Lavacharts object to an array
     *
     * @since 3.2.0
     * @return array
     */
    public function toArray()
    {
        return [
            'version'    => self::VERSION,
            'options'    => $this->options,
            'charts'     => $this->volcano->getCharts(),
            'dashboards' => $this->volcano->getDashboards(),
        ];
    }

    /**
     * Create a new DataTable using the DataFactory
     *
     * If the additional DataTablePlus package is available, then one will
     * be created, otherwise a standard DataTable is returned.
     *
     * @since  3.0.3
     * @uses   \Khill\Lavacharts\DataTables\DataFactory
     * @return \Khill\Lavacharts\DataTables\DataTable
     */
    public function DataTable()
    {
        return call_user_func_array(
            [DataFactory::class, 'DataTable'],
            func_get_args()
        );
    }

    /**
     * Create a new Dashboard
     *
     * @since  3.0.0
     * @param  string    $label
     * @param  DataTable $dataTable
     * @return \Khill\Lavacharts\Dashboards\Dashboard
     */
    public function Dashboard($label, DataTable $dataTable)
    {
        $label = Str::verify($label);

        if ($this->exists('Dashboard', $label)) {
            return $this->volcano->get('Dashboard', $label);
        }

        return $this->volcano->store(
            $this->dashFactory->create(func_get_args())
        );
    }

    /**
     * Create a new ControlWrapper from a Filter
     *
     * @since  3.0.0
     * @param  Filter $filter    Filter to wrap
     * @param  string $elementId HTML element ID to output the control.
     * @return ControlWrapper
     */
    public function ControlWrapper(Filter $filter, $elementId)
    {
        $elementId = Str::verify($elementId);

        return new ControlWrapper($filter, $elementId);
    }

    /**
     * Create a new ChartWrapper from a Chart
     *
     * @since  3.0.0
     * @param  Chart  $chart     Chart to wrap
     * @param  string $elementId HTML element ID to output the control.
     * @return ChartWrapper
     */
    public function ChartWrapper(Chart $chart, $elementId)
    {
        $elementId = Str::verify($elementId);

        return new ChartWrapper($chart, $elementId);
    }

    /**
     * Returns the Volcano instance.
     *
     * @return Volcano
     */
    public function getVolcano()
    {
        return $this->volcano;
    }

    /**
     * Returns the current locale used in the DataTable
     *
     * @deprecated 3.2.0 use $lava->getOption('locale')
     * @since  3.1.0
     * @return string
     */
    public function getLocale()
    {
        return $this->options['locale'];
    }

    /**
     * Locales are used to customize text for a country or language.
     *
     * This will affect the formatting of values such as currencies, dates, and numbers.
     *
     * By default, Lavacharts is loaded with the "en" locale. You can override this default
     * by explicitly specifying a locale when creating the DataTable.
     *
     * @deprecated 3.2.0 Set this option with the constructor, or with
     *                   $lava->options->set('locale', 'en');
     *
     * @since  3.1.0
     * @param  string $locale
     * @return $this
     * @throws \Khill\Lavacharts\Exceptions\InvalidStringValue
     */
    public function setLocale($locale = 'en')
    {
        $this->options['locale'] = Str::verify($locale);

        return $this;
    }

    /**
     * Outputs the lava.js module for manual placement.
     *
     * Will be depreciating jsapi in the future
     *
     * @since  3.0.3
     * @param array $options
     * @return string Google Chart API and lava.js script blocks
     */
    public function lavajs(array $options = [])
    {
        $this->options->merge($options);

        return (string) $this->scriptManager->getLavaJs($this->options);
    }

    /**
     * Outputs the link to the Google JSAPI
     *
     * @deprecated 3.0.3
     * @since      2.3.0
     * @param array $options
     * @return string Google Chart API and lava.js script blocks
     */
    public function jsapi(array $options = [])
    {
        return $this->lavajs($options);
    }

    /**
     * Checks to see if the given chart or dashboard exists in the volcano storage.
     *
     * @since  2.4.2
     * @param  string $type Type of object to isNonEmpty.
     * @param  string $label Label of the object to isNonEmpty.
     * @return boolean
     */
    public function exists($type, $label)
    {
        $label = Str::verify($label);

        if ($type == 'Dashboard') {
            return $this->volcano->checkDashboard($label);
        } else {
            return $this->volcano->checkChart($type, $label);
        }
    }

    /**
     * Fetches an existing Chart or Dashboard from the volcano storage.
     *
     * @since  3.0.0
     * @param  string $type  Type of Chart or Dashboard.
     * @param  string $label Label of the Chart or Dashboard.
     * @return Renderable
     * @throws \Khill\Lavacharts\Exceptions\InvalidRenderable
     */
    public function fetch($type, $label)
    {
        $label = Str::verify($label);

        if (strpos($type, 'Chart') === false && $type != 'Dashboard') {
            throw new InvalidRenderable($type);
        }

        return $this->volcano->get($type, $label);
    }

    /**
     * Stores a existing Chart or Dashboard into the volcano storage.
     *
     * @since  3.0.0
     * @param  Renderable $renderable A Chart or Dashboard.
     * @return Renderable
     */
    public function store(Renderable $renderable)
    {
        return $this->volcano->store($renderable);
    }

    /**
     * Renders Charts or Dashboards into the page
     *
     * Given a type, label, and HTML element id, this will output
     * all of the necessary javascript to generate the chart or dashboard.
     *
     * As of version 3.1, the elementId parameter is optional, but only
     * if the elementId was set explicitly to the Renderable.
     *
     * @since  2.0.0
     * @uses   \Khill\Lavacharts\Support\Buffer
     * @param  string $type       Type of object to render.
     * @param  string $label      Label of the object to render.
     * @param  mixed  $elementId  HTML element id to render into.
     * @param  mixed  $div        Set true for div creation, or pass an array with height & width
     * @return string
     */
    public function render($type, $label, $elementId = null, $div = false)
    {
        $label = Str::verify($label);

        if (is_string($elementId)) {
            $elementId = new ElementId($elementId);
        }

        if (is_array($elementId)) {
            $div = $elementId; // @TODO allow missing element ids to use renderable instance's id
        }

        if ($type == 'Dashboard') {
            $buffer = $this->renderDashboard($label, $elementId);
        } else {
            $buffer = $this->renderChart($type, $label, $elementId, $div);
        }

        return $buffer->getContents();
    }

    /**
     * Renders all charts and dashboards that have been defined.
     *
     *
     * Options can be passed in to override the default config.
     * Available options are defined in src/Laravel/config/lavacharts.php
     *
     * @since  3.1.0
     * @param array $options Options for rendering
     * @return string
     */
    public function renderAll(array $options = [])
    {
        $this->scriptManager->getOptions()->merge($options);

        $output = $this->scriptManager->getLavaJs($this->options);

        $renderables = $this->volcano->getAll();

        foreach ($renderables as $renderable) {
            $output->append(
                $this->scriptManager->getOutputBuffer($renderable)
            );
        }

        return $output->getContents();
    }

    /**
     * Renders the chart into the page
     *
     * Given a chart label and an HTML element id, this will output
     * all of the necessary javascript to generate the chart.
     *
     * @depreciated 3.2.0 It is tedious to make multiple render calls, favor renderAll()
     * @since  3.0.0
     * @param  string     $type
     * @param  string     $label
     * @param  string     $elementId HTML element id to render the chart into.
     * @param  bool|array $div       Set true for div creation, or pass an array with height & width
     * @return \Khill\Lavacharts\Support\Buffer
     * @throws \Khill\Lavacharts\Exceptions\ChartNotFound
     * @throws \Khill\Lavacharts\Exceptions\InvalidConfigValue
     * @throws \Khill\Lavacharts\Exceptions\InvalidDivDimensions
     */
    private function renderChart($type, $label, $elementId = '', $div = false)
    {
        /** @var \Khill\Lavacharts\Charts\Chart $chart */
        $chart = $this->volcano->get($type, $label);

        if (!$chart->getOptions()->has('elementId')) {
            $chart->setElementId(Str::verify($elementId));
        }

        $buffer = $this->scriptManager->getOutputBuffer($chart);

        if ($this->scriptManager->lavaJsRendered() === false) {
            $buffer->prepend($this->lavajs());
        }

        if ($div !== false) {
            $buffer->prepend(HtmlFactory::createDiv($chart->getElementId(), $div));
        }

        return $buffer;
    }

    /**
     * Renders the dashboard into the page.
     *
     * Given a chart label and an HTML element id, this will output
     * all of the necessary javascript to generate the chart.
     *
     * @depreciated 3.2.0 It is tedious to make multiple render calls, favor renderAll()
     * @since  3.0.0
     * @uses   Buffer $buffer
     * @param  string $label
     * @param  string $elementId HTML element id to render the chart into.
     * @return Buffer
     * @throws \Khill\Lavacharts\Exceptions\DashboardNotFound
     */
    private function renderDashboard($label, $elementId = '')
    {
        /** @var \Khill\Lavacharts\Dashboards\Dashboard $dashboard */
        $dashboard = $this->volcano->get('Dashboard', $label);

        if (!$dashboard->getOptions()->has('elementId')) {
            $dashboard->setElementId(Str::verify($elementId));
        }

        $buffer = $this->scriptManager->getOutputBuffer($dashboard);

        if ($this->scriptManager->lavaJsRendered() === false) {
            $buffer->prepend($this->lavajs());
        }

        return $buffer;
    }

    /**
     * Checks if running in composer environment
     *
     * This will check if the folder 'composer' is within the path to Lavacharts.
     *
     * @access private
     * @since  2.4.0
     * @return boolean
     */
    private function usingComposer()
    {
        if (strpos(realpath(__FILE__), 'composer') !== false) {
            return true;
        } else {
            return false;
        }
    }
}
