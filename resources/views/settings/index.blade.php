@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Google Sheets Settings</h4>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    {{-- Google Account Connection --}}
                    <div class="mb-4">
                        <h5>Step 1: Connect Google Account</h5>
                        @if ($settings && $settings->access_token)
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Google account is connected
                            </div>
                            <a href="{{ route('google.disconnect') }}" class="btn btn-danger" onclick="return confirm('Are you sure you want to disconnect your Google account?')">
                                <i class="fas fa-unlink"></i> Disconnect Google Account
                            </a>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Google account is not connected
                            </div>
                            <a href="{{ route('google.redirect') }}" class="btn btn-primary">
                                <i class="fab fa-google"></i> Connect Google Account
                            </a>
                        @endif
                    </div>

                    <hr>

                    {{-- Google Sheet Selection --}}
                    @if ($settings && $settings->access_token)
                        <div class="mb-4">
                            <h5>Step 2: Select Google Sheet</h5>

                            @if ($selectedSheet)
                                <div class="alert alert-info">
                                    <strong>Currently Selected:</strong> {{ $selectedSheet['title'] ?? 'Unknown' }}<br>
                                    <strong>Sheet Tab:</strong> {{ $settings->sheet_name ?? 'database' }}
                                </div>
                            @endif

                            <form action="{{ route('settings.save-spreadsheet') }}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label for="spreadsheet_id">Select Spreadsheet:</label>
                                    <select name="spreadsheet_id" id="spreadsheet_id" class="form-control" required>
                                        <option value="">-- Choose a spreadsheet --</option>
                                        @foreach ($spreadsheets as $sheet)
                                            <option value="{{ $sheet->getId() }}"
                                                {{ ($settings->spreadsheet_id ?? '') === $sheet->getId() ? 'selected' : '' }}>
                                                {{ $sheet->getName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Select the Google Sheet where orders will be synced. Make sure the sheet has a tab named "database" with the correct column structure.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="sheet_name">Sheet Tab Name:</label>
                                    <input type="text" name="sheet_name" id="sheet_name" class="form-control"
                                           value="{{ $settings->sheet_name ?? 'database' }}" placeholder="database">
                                    <small class="form-text text-muted">
                                        The name of the tab/sheet within the spreadsheet (default: "database")
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Sheet Configuration
                                </button>
                            </form>
                        </div>

                        <hr>

                        {{-- Test Connection --}}
                        @if ($settings->spreadsheet_id)
                            <div class="mb-4">
                                <h5>Step 3: Test Connection</h5>
                                <p class="text-muted">Test if the system can read from your selected Google Sheet.</p>
                                <a href="{{ route('settings.test-connection') }}" class="btn btn-info">
                                    <i class="fas fa-vial"></i> Test Connection
                                </a>
                            </div>
                        @endif

                        <hr>

                        {{-- Sheet Structure Info --}}
                        <div class="mb-4">
                            <h5>Required Sheet Structure</h5>
                            <p class="text-muted">Your Google Sheet must have the following column headers in the first row:</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>A</th>
                                            <th>B</th>
                                            <th>C</th>
                                            <th>D</th>
                                            <th>E</th>
                                            <th>F</th>
                                            <th>G</th>
                                            <th>H</th>
                                            <th>I</th>
                                            <th>J</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Month</td>
                                            <td>Date</td>
                                            <td>Name</td>
                                            <td>Order Number</td>
                                            <td>Country</td>
                                            <td>Product</td>
                                            <td>Price</td>
                                            <td>Status</td>
                                            <td>Reason for Return</td>
                                            <td>Main Product</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info">
                                <strong>Example data:</strong><br>
                                <code>Jan | 02/01/2025 | Sanela Rippitsch | 10603062224223 | Österreich | serena-white-38 | € 499 | zurück | Artikel gefällt nicht | serena</code>
                            </div>
                        </div>

                        <hr>

                        {{-- Sync Status --}}
                        <div class="mb-4">
                            <h5>Sync Status</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-sync-alt"></i> Orders are automatically synced every 10-30 minutes.<br>
                                <small>The cron job will fetch orders from the last 30 days and update the sheet automatically.</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
