<?php

namespace SilverStripeDashboard\Models;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * Class DashboardWeatherPanel
 * @package SilverStripeDashboard\Models
 */
class DashboardWeatherPanel extends DashboardPanel
{

    /**
     * @var string
     */
    private static $table_name = 'DashboardWeatherPanel';
    
    /**
     * @var array
     */
    private static $db = array(
        'Location'     => 'Varchar',
        'LocationType' => "Enum('city,code','city')",
        'Units'        => "Enum('c,f','c')",
        'WeatherHTML'  => 'HTMLText'
    );

    /**
     * @var string
     */
    private static $icon = "dashboard/images/weather.png";

    /**
     * @var bool
     */
    private static $configure_on_create = true;

    /**
     * @return string
     */
    public function getLabel()
    {
        return _t('Dashboard.WEATHER', 'Weather');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return _t('Dashboard.WEATHERDESCRIPTION', 'Shows the weather for a given location.');
    }

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getConfiguration()
    {
        $fields = parent::getConfiguration();
        $fields->push(TextField::create("Location", _t('Dashboard.LOCATION', 'Location')));
        // Added support for fetching by citycode if the city is not found.
        $fields->push(OptionsetField::create("LocationType", _t('Dashboard.TYPE', 'Location type'), array(
            'city' => _t('Dashboard.CITY', 'City'),
            'code' => _t('Dashboard.CODE', 'City code'),
        )));
        $fields->push(DropdownField::create("Units", _t('Dashboard.UNITS', 'Units'), array(
            'c' => _t('Dashboard.CELCIUS', 'Celcius'),
            'f' => _t('Dashboard.FARENHEIT', 'Farenheit')
        ))->addExtraClass("no-chzn"));

        return $fields;
    }

    /**
     * @return bool|mixed|\SilverStripe\ORM\FieldType\DBHTMLText
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function Weather()
    {
        if (!$this->Location) {
            return false;
        }
        $rnd = time();
        $url = "http://query.yahooapis.com/v1/public/yql?format=json&rnd={$rnd}&diagnostics=true&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&q=";
        // Added support for citycode if the city is not found.
        if ($this->LocationType == 'city') {
            $query = urlencode("select * from weather.forecast where location in (select id from weather.search where query=\"{$this->Location}\") and u=\"{$this->Units}\"");
        } else {
            $query = urlencode("select * from weather.forecast where location=\"{$this->Location}\" and u=\"{$this->Units}\"");
        }
        $response = file_get_contents($url . $query);
        if ($response) {
            $result = Convert::json2array($response);
            if (!$result["query"]["results"]) {
                return false;
            }

            $days = ArrayList::create(array());
            $channel = isset($result["query"]["results"]["channel"][0]) ? $result["query"]["results"]["channel"][0]
                : $result["query"]["results"]["channel"];
            if (!isset($channel["link"])) {
                return false;
            }
            $label = $channel["title"];
            $link = $channel["link"];

            $forecast = $channel["item"]["forecast"];
            for ($i = 0; $i < 2; $i++) {
                $item = $forecast[$i];
                $days->push(ArrayData::create(array(
                    'High'     => $item["high"],
                    'Low'      => $item["low"],
                    'ImageURL' => "http://l.yimg.com/a/i/us/we/52/" . $item["code"] . ".gif",
                    'Label'    => $i == 0 ? _t('Dashboard.TODAY', 'Today') : _t('Dashboard.TOMORROW', 'Tomorrow')
                )));
            }

            $html = $this->customise(array(
                'Location' => str_replace("Yahoo! Weather - ", "", $label),
                'Link'     => $link,
                'Days'     => $days
            ))->renderWith('Includes/DashboardWeatherContent');
            $this->WeatherHTML = $html;
            $this->write();

            return $html;
        }

        return $this->WeatherHTML;
    }

    /**
     * @return \SilverStripeDashboard\Models\SSViewer
     */
    public function PanelHolder()
    {
        Requirements::css("unclecheese/dashboard: client/dist/styles/dashboard-weather.css");

        return parent::PanelHolder();
    }

}
