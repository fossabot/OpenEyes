<?php

/**
 * Created by PhpStorm.
 * User: petergallagher
 * Date: 05/05/2016
 * Time: 13:20
 */
class AllSurgeonsPcrRiskReport extends PcrRiskReport
{
    /**
     * @var string
     */
    protected $searchTemplate = 'application.modules.OphTrOperationnote.views.report.all_surgeons_search';

    protected $site = '';

	protected $operationCount = 0;

    protected $graphConfig = array(
        'chart' => array('renderTo' => '', 'type' => 'spline', 'zoomType' => 'xy',),
        'title' => array('text' => 'PCR Rate (risk adjusted)'),
        'subtitle' => array('text' => 'Total Operations: 0'),
        'xAxis' => array(
            'title' => array('text' => 'No. Operations')
        ),
        'yAxis' => array(
            'title' => array('text' => 'PCR Rate'),
            'plotLines' => array(array(
                'value' => 0,
                'color' => 'black',
                'dashStyle' => 'shortdash',
                'width' => 1,
                'label' => array('text' => 'Average')
            )),
            'max' => 30
        ),
        'tooltip' => array(
            'headerFormat' => '<b>{series.name}</b><br>',
            'pointFormat' => '<i>Operations</i>: {point.x} <br /> <i>PCR Avg</i>: {point.y:.2f}'
        ),
        'legend' => array(
            'enabled' => false
        ),
        'plotOptions' => array('spline' => array('marker' => array(
            'enabled' => false,
            'states' => array('hover' => array('enabled' => false))
        ))),
    );

    /**
     * @param $app
     */
    public function __construct($app)
    {
        $this->site = $app->getRequest()->getQuery('site', '');

        parent::__construct($app);
    }


    /**
     * @return string
     */
    public function seriesJson()
    {
        $this->command->reset();
        $this->command->select('surgeon_id, CONCAT_WS(" ",first_name, last_name) AS surgeon_name')
            ->from('et_ophtroperationnote_cataract')
            ->join('event', 'et_ophtroperationnote_cataract.event_id = event.id')
            ->join('et_ophtroperationnote_surgeon', 'et_ophtroperationnote_surgeon.event_id = event.id')
            ->join('user', 'surgeon_id = user.id')
            ->group('surgeon_id');

        $surgeons = $this->command->queryAll();
        $this->series = array();
        foreach ($surgeons as $surgeon) {
            $this->surgeon = $surgeon['surgeon_id'];
            $this->series[] = array(
                'name' => $surgeon['surgeon_name'],
                'type' => 'scatter',
                'data' => $this->dataSet()
            );
        }


        $this->series[] = array(
            'name' => 'Upper 99.8%',
            'data' => $this->upper98(),
            'color' => 'red',
        );


        $this->series[] = array(
            'name' => 'Upper 95%',
            'data' => $this->upper95(),
            'color' => 'green',
        );

			// set the graph subtitle here, so we don't have to run this query more than once
			$this->graphConfig['subtitle']['text'] = 'Total Operations: ' . $this->operationCount;

        return json_encode($this->series);
    }

    /**
     * @return mixed|string
     */
    public function renderSearch()
    {
        $sites = Site::model()->findAll(array('condition' => "active=1 and short_name !=''", 'order' => 'short_name'));
        return $this->app->controller->renderPartial($this->searchTemplate, array('report' => $this, 'sites' => $sites));
    }

    /**
     * @param $surgeon
     * @param $dateFrom
     * @param $dateTo
     * @param $site
     * @return CDbDataReader|mixed
     */
    protected function queryData($surgeon, $dateFrom, $dateTo, $site = '')
    {
        $this->command->reset();
        $this->command->select('ophtroperationnote_cataract_complications.name as complication, pcr_risk as risk')
            ->from('et_ophtroperationnote_cataract')
            ->join('event', 'et_ophtroperationnote_cataract.event_id = event.id')
            ->join('et_ophtroperationnote_surgeon', 'et_ophtroperationnote_surgeon.event_id = event.id')
            ->leftJoin('ophtroperationnote_cataract_complication', 'et_ophtroperationnote_cataract.id = ophtroperationnote_cataract_complication.cataract_id')
            ->leftJoin('ophtroperationnote_cataract_complications', 'ophtroperationnote_cataract_complications.id = ophtroperationnote_cataract_complication.complication_id')
            ->where('surgeon_id = :surgeon', array('surgeon' => $surgeon))
            ->andWhere('event.deleted=0');


        if ($dateFrom) {
            $this->command->andWhere('event.event_date >= :dateFrom', array('dateFrom' => $dateFrom));
        }

        if ($dateTo) {
            $this->command->andWhere('event.event_date <= :dateTo', array('dateTo' => $dateTo));
        }

        if ($site) {
            $this->command->join('et_ophtroperationnote_site_theatre', 'et_ophtroperationnote_site_theatre.event_id = event.id')->andWhere('site_id = :site', array('site' => $site));
        }

        return $this->command->queryAll();
    }

    /**
     * @return array
     */
    public function dataSet()
    {
        $data = $this->queryData($this->surgeon, $this->from, $this->to, $this->site);

        $total = $this->getTotalOperations($this->site);
        $pcrCases = 0;
        $pcrRiskTotal = 0;
        $adjustedPcrRate = 0;

        foreach ($data as $case) {
            if (isset($case['complication']) && ($case['complication'] === 'PC rupture' || $case['complication'] === 'PC rupture with vitreous loss' || $case['complication'] === 'PC rupture no vitreous loss')) {
                $pcrCases++;
            }
            if (isset($case['risk']) && $case['risk'] != "") {
                $pcrRiskTotal += $case['risk'];
            } else {
                $pcrRiskTotal += 1.92;
            }
        }

        if ($total !== 0 && (int)$pcrRiskTotal !== 0) {
            $adjustedPcrRate = (($pcrCases / $total) / ($pcrRiskTotal / $total)) * $this->average();
        }


        if ($total > 1000) {
            $this->totalOperations = $total;
        }

				$this->operationCount += $total;

        return array(array($total, $adjustedPcrRate));
    }

    public function getTotalOperations($site = '')
    {
        $this->command->reset();
        $this->command->select('COUNT(*) as total')
            ->from('et_ophtroperationnote_cataract')
            ->join('event', 'et_ophtroperationnote_cataract.event_id = event.id')
            ->join('et_ophtroperationnote_surgeon', 'et_ophtroperationnote_surgeon.event_id = event.id')
            ->where('surgeon_id = :surgeon', array('surgeon' => $this->surgeon))
            ->andWhere('event.deleted=0');

        if ($this->from) {
            $this->command->andWhere('event.event_date >= :dateFrom', array('dateFrom' => $this->from));
        }

        if ($this->to) {
            $this->command->andWhere('event.event_date <= :dateTo', array('dateTo' => $this->to));
        }

        if ($site) {
            $this->command->join('et_ophtroperationnote_site_theatre', 'et_ophtroperationnote_site_theatre.event_id = event.id')->andWhere('site_id = :site', array('site' => $site));
        }

        $totalData = $this->command->queryAll();
        return (int)$totalData[0]["total"];
    }

}