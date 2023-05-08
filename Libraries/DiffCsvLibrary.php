<?php

namespace App\Libraries;

use Exception;

/**
 * A class that compares two CSV files and returns the rows that are not present in the first file.
 *
 * @package App\Libraries
 * @author Oleg Kosarev <dev.oleg.kosarev@outlook.com>
 * @repository https://github.com/DevOlegKosarev/DiffCsvLibrary
 * @license MIT
 * @version 1.0.0
 * @example $diff = new DiffCsvLibrary();
 *          $result = $diff->diff('file1.csv', 'file2.csv', 'id');
 *          print_r($result);
 */
class DiffCsvLibrary
{
    /**
     * Compare two CSV files and return the rows that are not present in the first file.
     *
     * @param string $existingTranslations The path to the first CSV file
     * @param string $currentTranslationData The path to the second CSV file
     * @param string $sharedUniqueId The name of the column that contains the unique identifier for each row
     * @return array An array with two keys: "totalRowsSkipped" and "diffedRows"
     * @throws Exception If an error occurs while reading or parsing the files
     */
    public function diff($existingTranslations, $currentTranslationData, $sharedUniqueId)
    {
        try {
            // Open the first file for reading
            $handle = fopen($existingTranslations, 'r');
            // Initialize an array to store the unique identifiers from the first file
            $uniqueIds = [];
            // Initialize a counter for the loop
            $loop = 1;
            // Initialize a variable to store the index of the column with the unique identifier
            $uniqueIdRowIndex = null;
            // Loop through each row of the first file
            while ($row = fgetcsv($handle, 0, ',')) {
                // If this is the first row, find the index of the column with the unique identifier
                if ($loop === 1) {
                    $uniqueIdRowIndex = $this->getIndexOf($sharedUniqueId, $row);
                    // If the column is not found, throw an exception
                    if (is_null($uniqueIdRowIndex)) {
                        throw new \LogicException("Cannot find unique header {$sharedUniqueId} in {$existingTranslations}");
                    }
                }
                // If this is not the first row, add the unique identifier to the array
                if ($loop > 1) {
                    $uniqueIds[] = $row[$uniqueIdRowIndex];
                }
                // Increment the counter
                $loop++;
            }
            // Close the first file
            fclose($handle);
            // Open the second file for reading
            $handle = fopen($currentTranslationData, 'r');
            // Initialize a counter for the loop
            $loop = 1;
            // Initialize a variable to store the index of the column with the unique identifier
            $uniqueIdRowIndex = null;
            // Initialize an array to store the rows that are not present in the first file
            $diffedRows = [];
            // Initialize an array to store the result
            $result = [];
            // Initialize a counter for the skipped rows
            $cSkipped = 0;
            // Loop through each row of the second file
            while ($row = fgetcsv($handle, 0, ',')) {
                // If this is the first row, find the index of the column with the unique identifier
                if ($loop === 1) {
                    $uniqueIdRowIndex = $this->getIndexOf($sharedUniqueId, $row);
                    // If the column is not found, throw an exception
                    if (is_null($uniqueIdRowIndex)) {
                        throw new Exception("Cannot find unique header {$sharedUniqueId} in {$currentTranslationData}");
                    }
                }
                // If this is not the first row, get the unique identifier from this row
                if ($loop > 1) {
                    $id = $row[$uniqueIdRowIndex];
                    // If this identifier is already present in the first file, increment the skipped counter
                    if (in_array($id, $uniqueIds)) {
                        $cSkipped++;
                    } else {
                        // Otherwise, add this row to the diffed rows array with the identifier as key
                        $diffedRows[$id] = $row;
                    }
                }
                // Increment the counter
                $loop++;
            }
            // Close the second file
            fclose($handle);
            // Add the skipped counter and diffed rows array to the result array
            $result["totalRowsSkipped"] = $cSkipped;
            $result["diffedRows"] = $diffedRows;
            return $result;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function getIndexOf($value, $arrayData)
    {
        foreach ($arrayData as $index => $itemValue) {
            if ($itemValue === $value) {
                return $index;
            }
        }
        return null;
    }
}
