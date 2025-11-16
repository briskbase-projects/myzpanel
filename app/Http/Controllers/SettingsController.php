<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\GoogleSheetsAPI;

class SettingsController extends Controller
{
    use GoogleSheetsAPI;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display settings page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = DB::table('google_settings')
            ->where('is_active', true)
            ->first();

        $spreadsheets = [];
        $selectedSheet = null;

        // If user is connected, fetch their spreadsheets
        if ($settings && $settings->access_token) {
            $spreadsheets = $this->listUserSpreadsheets();

            // Get info about selected spreadsheet if exists
            if ($settings->spreadsheet_id) {
                $selectedSheet = $this->getSpreadsheetInfo($settings->spreadsheet_id);
            }
        }

        return view('settings.index', compact('settings', 'spreadsheets', 'selectedSheet'));
    }

    /**
     * Save selected spreadsheet
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveSpreadsheet(Request $request)
    {
        try {
            $request->validate([
                'spreadsheet_id' => 'required|string',
                'sheet_name' => 'nullable|string',
            ]);

            $settings = DB::table('google_settings')
                ->where('is_active', true)
                ->first();

            if (!$settings) {
                return redirect()->route('settings.index')->with('error', 'Please connect your Google account first.');
            }

            // Validate that the sheet exists
            $sheetInfo = $this->getSpreadsheetInfo($request->spreadsheet_id);
            if (!$sheetInfo) {
                return redirect()->route('settings.index')->with('error', 'Cannot access the selected spreadsheet. Please check permissions.');
            }

            // If sheet_name is provided, validate it exists in the spreadsheet
            $sheetName = $request->sheet_name ?? 'database';
            if (!in_array($sheetName, $sheetInfo['sheets'])) {
                return redirect()->route('settings.index')->with('error', 'Sheet "' . $sheetName . '" not found in the spreadsheet.');
            }

            // Update settings
            DB::table('google_settings')
                ->where('id', $settings->id)
                ->update([
                    'spreadsheet_id' => $request->spreadsheet_id,
                    'sheet_name' => $sheetName,
                    'updated_at' => now(),
                ]);

            return redirect()->route('settings.index')->with('success', 'Google Sheet configured successfully! Sync will start automatically.');
        } catch (\Exception $e) {
            Log::error('Error saving spreadsheet: ' . $e->getMessage());
            return redirect()->route('settings.index')->with('error', 'Failed to save spreadsheet configuration.');
        }
    }

    /**
     * Test sheet connection
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testConnection()
    {
        try {
            $config = $this->getSheetConfig();
            if (!$config) {
                return redirect()->route('settings.index')->with('error', 'No spreadsheet configured.');
            }

            $sheetInfo = $this->getSpreadsheetInfo($config['spreadsheet_id']);
            if (!$sheetInfo) {
                return redirect()->route('settings.index')->with('error', 'Cannot access spreadsheet. Please check your connection.');
            }

            $rows = $this->readSheetData();

            return redirect()->route('settings.index')->with('success', 'Connection successful! Found ' . count($rows) . ' rows in the sheet.');
        } catch (\Exception $e) {
            Log::error('Test connection error: ' . $e->getMessage());
            return redirect()->route('settings.index')->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }
}
