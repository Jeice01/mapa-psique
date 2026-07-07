<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Database\Repositories\MapRepository;
use App\Database\Repositories\PatientRepository;

final class DashboardService
{
    public function __construct(
        private readonly PatientRepository $patients = new PatientRepository(),
        private readonly MapRepository $maps = new MapRepository()
    ) {
    }

    /**
     * @return array{patients_count:int,maps_count:int,draft_maps_count:int,analyzed_maps_count:int}
     */
    public function summary(string $ownerUserId): array
    {
        $mapCounts = $this->maps->countDashboardByOwner($ownerUserId);

        return [
            'patients_count' => $this->patients->countByOwner($ownerUserId),
            'maps_count' => $mapCounts['maps_count'],
            'draft_maps_count' => $mapCounts['draft_maps_count'],
            'analyzed_maps_count' => $mapCounts['analyzed_maps_count'],
        ];
    }
}
