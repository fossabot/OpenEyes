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
						->where('event.event_date >= DATE_SUB(NOW(),INTERVAL 1 YEAR);');

        $surgeons = $this->command->queryAll();
        $this->series = array(
        );
        foreach($surgeons as $surgeon){
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



        $this->series[]  =  array(
            'name' => 'Upper 95%',
            'data' => $this->upper95(),
            'color' => 'green',
        );


        return json_encode($this->series);
    }
}