<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class TeamHierarchyService
{
    private array $flatHierarchy = [];

    // TODO: Add validation for csv file
    public function processCSV(UploadedFile $file, ?string $filterQuery): array
    {
        // Parse the CSV file into an array
        $csvData = array_map('str_getcsv', file($file->getPathname()));
        array_walk($csvData, function (&$a) use ($csvData) {
            $a = array_combine($csvData[0], $a);
        });
        array_shift($csvData);

        // Store each team data in flatHierarchy property
        foreach ($csvData as $row) {
            $teamName = $row['team'];
            $this->flatHierarchy[$teamName] = [
                'teamName' => $teamName,
                'parentTeam' => $row['parent_team'] ?? '',
                'managerName' => $row['manager_name'] ?? '',
                'businessUnit' => $row['business_unit'] ?? '',
                'teams' => []
            ];
        }

        // dd($this->flatHierarchy);

        $hierarchy = $this->buildHierarchy();

        // If filter query is provided, filter the hierarchy by the provided team
        if ($filterQuery) {
            return $this->filterHierarchyByTeam($filterQuery);
        }

        return $hierarchy;
    }

    private function buildHierarchy(): array
    {
        $hierarchy = [];
        $flatHierarchy = $this->flatHierarchy;

        // Go over the flat hierarchy and organize teams under their parent teams
        foreach ($flatHierarchy as $teamName => $teamData) {
            // if ($teamName == 'Sales') {
            //     dd($teamData)
            // }

            // If the team has no parent, it's a root node
            if (empty($teamData['parentTeam'])) {
                $hierarchy[$teamName] = &$flatHierarchy[$teamName];
            } else {
                // If the team has a parent, add it as a sub-team under its parent
                $flatHierarchy[$teamData['parentTeam']]['teams'][$teamName] = &$flatHierarchy[$teamName];
            }
        }

        return $hierarchy;
    }

    public function filterHierarchyByTeam(string $teamName): ?array
    {
        // Check if the specified team exists
        if (! isset($this->flatHierarchy[$teamName])) {
            return null;
        }

        // Create a filtered version of the hierarchy that includes only the branch up to the specified team
        $filteredHierarchy = $this->buildFilteredHierarchy($teamName);
        // dd($filteredHierarchy)
        // Convert the flat filtered hierarchy into a nested structure
        return $this->restructureHierarchy($filteredHierarchy);
    }

    private function buildFilteredHierarchy(string $teamName): array
    {
        $filteredHierarchy = [];
        $currentTeam = $teamName;

        // Travel up the hierarchy from the specified team to the root
        while ($currentTeam !== '') {
            $teamData = $this->flatHierarchy[$currentTeam];
            $filteredHierarchy[$currentTeam] = $teamData;
            $currentTeam = $teamData['parentTeam']; // Move up to the parent team
        }

        // Reverse the array to get the hierarchy in root first order
        return array_reverse($filteredHierarchy);
    }

    private function restructureHierarchy(array $flatFilteredHierarchy): array
    {
        $restructured = [];
        $current = &$restructured;

        // Iterate through the flat filtered hierarchy to build the nested structure
        foreach ($flatFilteredHierarchy as $teamName => $teamData) {
            $current[$teamName] = $teamData;
            $current[$teamName]['teams'] = []; // Initialize the sub-teams array
            $current = &$current[$teamName]['teams']; // Move down to the next level in the hierarchy
        }

        return $restructured;
    }
}
