<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class NewVisitReportComponent1 extends Component
{
    public $format;
    public $shopDetailsList = [];
    public $allHierarchyLevels = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $format_id = Session::get('format_id');
        $wave_id1 = Session::get('wave_id1');

        $this->format = DB::table('formats')
            ->join('hierarchylevels', 'formats.assignHID', '=', 'hierarchylevels.HID')
            ->where('formats.id', $format_id)
            ->select('formats.*', 'hierarchylevels.*')
            ->get();

        $this->shopDetailsList = DB::table('assignshops')
            ->where('format_id', $format_id)
            ->where('wave_id', $wave_id1)
            ->where('status', "submit to client")
            ->get();

        $this->allHierarchyLevels = [];
        foreach ($this->shopDetailsList as $shopDetails) {
            $locationID = $shopDetails->location_id;
            $shopID = $shopDetails->id;
            $query = "
                WITH RECURSIVE HierarchyCTE AS (
                    SELECT 
                        h.id AS hierarchy_id,
                        h.levelID AS level_id,
                        hl.hierarchylavelname AS level_name,
                        hl.level AS level,
                        hl.HID AS hid,
                        l.locationname AS location_name,
                        h.branch_code AS branch_code,
                        h.address AS address,
                        h.parentID AS parent_id
                    FROM 
                        hierarchies h
                    INNER JOIN 
                        hierarchylevels hl ON h.levelID = hl.id
                    INNER JOIN 
                        locations l ON h.LID = l.id
                    WHERE 
                        h.id = :initialHierarchyId
            
                    UNION ALL
            
                    SELECT 
                        h.id AS hierarchy_id,
                        h.levelID AS level_id,
                        hl.hierarchylavelname AS level_name,
                        hl.level AS level,
                        hl.HID AS hid,
                        l.locationname AS location_name,
                        h.branch_code AS branch_code,
                        h.address AS address,
                        h.parentID AS parent_id
                    FROM 
                        hierarchies h
                    INNER JOIN 
                        hierarchylevels hl ON h.levelID = hl.id
                    INNER JOIN 
                        locations l ON h.LID = l.id
                    INNER JOIN 
                        HierarchyCTE hc ON hc.parent_id = h.id
                )
                SELECT 
                    hierarchy_id,
                    level_id,
                    level_name,
                    level,
                    hid,
                    location_name,
                    branch_code,
                    address
                FROM 
                    HierarchyCTE;
            ";

            $hierarchyLevels = DB::select($query, ['initialHierarchyId' => $locationID]);
            $hierarchyLevels = array_reverse($hierarchyLevels);
            // dd($hierarchyLevels);
            // dd($locationID);
            $this->allHierarchyLevels[] = [
                'locationID' => $locationID,
                'hierarchyLevels' => $hierarchyLevels,
                'shopID' =>  $shopID
            ];
            // dd($this->allHierarchyLevels);
        }
    }

    public function render()
    {
        return view('livewire.new-visit-report-component1', [
            'headerName' => $this->format,
            'reports' => $this->allHierarchyLevels,
        ]);
    }
}
