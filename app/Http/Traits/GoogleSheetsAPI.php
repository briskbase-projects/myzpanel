<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait GoogleSheetsAPI
{
    /**
     * Get Google Client instance with authentication
     *
     * @return \Google_Client|null
     */
    public function getGoogleClient()
    {
        try {
            $settings = DB::table('google_settings')
                ->where('is_active', true)
                ->first();

            if (!$settings || !$settings->access_token) {
                return null;
            }

            $client = new \Google_Client();
            $client->setApplicationName('Irvalda Zalando Integration');
            $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            // Set client credentials from .env (required for token refresh)
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

            // Set access token
            $client->setAccessToken([
                'access_token' => $settings->access_token,
                'refresh_token' => $settings->refresh_token,
                'token_type' => $settings->token_type ?? 'Bearer',
                'expires_in' => $settings->expires_in ?? 3600,
                'created' => $settings->token_created_at ? strtotime($settings->token_created_at) : time(),
            ]);

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                if ($settings->refresh_token) {
                    Log::info('Google access token expired, refreshing...');
                    $client->fetchAccessTokenWithRefreshToken($settings->refresh_token);
                    $newToken = $client->getAccessToken();

                    // Update tokens in database
                    DB::table('google_settings')
                        ->where('id', $settings->id)
                        ->update([
                            'access_token' => $newToken['access_token'],
                            'token_created_at' => date('Y-m-d H:i:s', $newToken['created']),
                            'updated_at' => now(),
                        ]);

                    Log::info('Google access token refreshed successfully');
                } else {
                    Log::error('No refresh token available, user needs to reconnect Google account');
                    return null;
                }
            }

            return $client;
        } catch (\Exception $e) {
            Log::error('Google Client initialization error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Google Sheets Service
     *
     * @return \Google_Service_Sheets|null
     */
    public function getGoogleSheetsService()
    {
        $client = $this->getGoogleClient();
        if (!$client) {
            return null;
        }

        return new \Google_Service_Sheets($client);
    }

    /**
     * Get configured spreadsheet ID and sheet name
     *
     * @return array|null ['spreadsheet_id' => string, 'sheet_name' => string]
     */
    public function getSheetConfig()
    {
        $settings = DB::table('google_settings')
            ->where('is_active', true)
            ->first();

        if (!$settings || !$settings->spreadsheet_id) {
            return null;
        }

        return [
            'spreadsheet_id' => $settings->spreadsheet_id,
            'sheet_name' => $settings->sheet_name ?? 'database',
        ];
    }

    /**
     * Read all rows from Google Sheet
     *
     * @return array
     */
    public function readSheetData()
    {
        try {
            $service = $this->getGoogleSheetsService();
            $config = $this->getSheetConfig();

            if (!$service || !$config) {
                return [];
            }

            $range = $config['sheet_name'] . '!A2:J'; // Skip header row, read columns A to J
            $response = $service->spreadsheets_values->get($config['spreadsheet_id'], $range);

            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            Log::error('Error reading sheet data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Append row to Google Sheet
     *
     * @param array $rowData
     * @param bool $applyRedText Whether to apply red text formatting
     * @return bool
     */
    public function appendSheetRow($rowData, $applyRedText = false)
    {
        try {
            $service = $this->getGoogleSheetsService();
            $config = $this->getSheetConfig();

            if (!$service || !$config) {
                return false;
            }

            $range = $config['sheet_name'] . '!A:J';
            $values = [$rowData];
            $body = new \Google_Service_Sheets_ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            $result = $service->spreadsheets_values->append(
                $config['spreadsheet_id'],
                $range,
                $body,
                $params
            );

            // Apply red text formatting if needed
            if ($applyRedText) {
                // Get the row number that was just added
                $updatedRange = $result->getUpdates()->getUpdatedRange();
                preg_match('/!A(\d+)/', $updatedRange, $matches);
                if (isset($matches[1])) {
                    $rowNumber = (int)$matches[1];
                    $this->formatRowRed($rowNumber);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error appending to sheet: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update specific row in Google Sheet
     *
     * @param int $rowNumber (1-indexed, header is row 1, data starts at row 2)
     * @param array $rowData
     * @param bool $applyRedText Whether to apply red text formatting
     * @return bool
     */
    public function updateSheetRow($rowNumber, $rowData, $applyRedText = false)
    {
        try {
            $service = $this->getGoogleSheetsService();
            $config = $this->getSheetConfig();

            if (!$service || !$config) {
                return false;
            }

            $range = $config['sheet_name'] . '!A' . $rowNumber . ':J' . $rowNumber;
            $values = [$rowData];
            $body = new \Google_Service_Sheets_ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            $service->spreadsheets_values->update(
                $config['spreadsheet_id'],
                $range,
                $body,
                $params
            );

            // Apply red text formatting if needed
            if ($applyRedText) {
                $this->formatRowRed($rowNumber);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating sheet row: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format a row with red text color
     *
     * @param int $rowNumber (1-indexed)
     * @return bool
     */
    public function formatRowRed($rowNumber)
    {
        try {
            $service = $this->getGoogleSheetsService();
            $config = $this->getSheetConfig();

            if (!$service || !$config) {
                return false;
            }

            // Get sheet ID
            $spreadsheet = $service->spreadsheets->get($config['spreadsheet_id']);
            $sheets = $spreadsheet->getSheets();
            $sheetId = null;

            foreach ($sheets as $sheet) {
                if ($sheet->getProperties()->getTitle() === $config['sheet_name']) {
                    $sheetId = $sheet->getProperties()->getSheetId();
                    break;
                }
            }

            if ($sheetId === null) {
                Log::error('Sheet not found: ' . $config['sheet_name']);
                return false;
            }

            // Create format request for red text
            $requests = [
                new \Google_Service_Sheets_Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $rowNumber - 1, // 0-indexed
                            'endRowIndex' => $rowNumber,
                            'startColumnIndex' => 0, // Column A
                            'endColumnIndex' => 10  // Column J
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'textFormat' => [
                                    'foregroundColor' => [
                                        'red' => 1.0,
                                        'green' => 0.0,
                                        'blue' => 0.0
                                    ]
                                ]
                            ]
                        ],
                        'fields' => 'userEnteredFormat.textFormat.foregroundColor'
                    ]
                ])
            ];

            $batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);

            $service->spreadsheets->batchUpdate($config['spreadsheet_id'], $batchUpdateRequest);

            return true;
        } catch (\Exception $e) {
            Log::error('Error formatting row red: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find row number by order number and SKU
     *
     * @param string $orderNumber
     * @param string $sku
     * @return int|null Row number (1-indexed) or null if not found
     */
    public function findSheetRowByOrder($orderNumber, $sku)
    {
        try {
            $rows = $this->readSheetData();

            // Data starts at row 2 (row 1 is header)
            foreach ($rows as $index => $row) {
                $rowOrderNumber = $row[3] ?? ''; // Column D (Order Number)
                $rowProduct = $row[5] ?? ''; // Column F (Product/SKU)

                if ($rowOrderNumber === $orderNumber && $rowProduct === $sku) {
                    return $index + 2; // +2 because array is 0-indexed and we skip header row
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error finding sheet row: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * List all spreadsheets accessible by the authenticated user
     *
     * @return array
     */
    public function listUserSpreadsheets()
    {
        try {
            $client = $this->getGoogleClient();
            if (!$client) {
                return [];
            }

            $driveService = new \Google_Service_Drive($client);

            $optParams = [
                'q' => "mimeType='application/vnd.google-apps.spreadsheet'",
                'pageSize' => 100,
                'fields' => 'files(id, name, modifiedTime)',
                'orderBy' => 'modifiedTime desc'
            ];

            $results = $driveService->files->listFiles($optParams);

            return $results->getFiles();
        } catch (\Exception $e) {
            Log::error('Error listing spreadsheets: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get spreadsheet metadata
     *
     * @param string $spreadsheetId
     * @return array|null
     */
    public function getSpreadsheetInfo($spreadsheetId)
    {
        try {
            $service = $this->getGoogleSheetsService();
            if (!$service) {
                return null;
            }

            $spreadsheet = $service->spreadsheets->get($spreadsheetId);

            return [
                'title' => $spreadsheet->getProperties()->getTitle(),
                'sheets' => array_map(function ($sheet) {
                    return $sheet->getProperties()->getTitle();
                }, $spreadsheet->getSheets())
            ];
        } catch (\Exception $e) {
            Log::error('Error getting spreadsheet info: ' . $e->getMessage());
            return null;
        }
    }
}
