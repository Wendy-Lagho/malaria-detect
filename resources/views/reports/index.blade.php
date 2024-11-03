<x-app-layout>
    @section('title', 'Reports Dashboard')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Total Reports Generated</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $statistics['total_reports'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Positive Cases</div>
                        <div class="mt-1 text-3xl font-semibold text-red-600">{{ $statistics['positive_cases'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Negative Cases</div>
                        <div class="mt-1 text-3xl font-semibold text-green-600">{{ $statistics['negative_cases'] }}</div>
                    </div>
                </div>
            </div>

          <!-- Add this button section with Alpine.js -->
            <div class="mb-6" x-data="{ 
                generating: false,
                error: null,
                async generateReport(analysisId) {
                    this.generating = true;
                    this.error = null;
                    
                    try {
                        const response = await fetch(`/reports/generate/${analysisId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            window.location.href = data.download_url;
                        } else {
                            this.error = data.message || 'Failed to generate report';
                        }
                    } catch (err) {
                        this.error = 'An error occurred while generating the report';
                    } finally {
                        this.generating = false;
                    }
                }
                }">
                <button 
                    @click="generateReport($event.target.dataset.analysisId)"
                    :disabled="generating"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <template x-if="generating">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="generating ? 'Generating Report...' : 'Generate Report'"></span>
                </button>
                
                <!-- Error Alert -->
                <template x-if="error">
                    <div class="mt-2 bg-red-50 border-l-4 border-red-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700" x-text="error"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Reports Table -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Analysis Reports</h2>

                    @if($reports->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500">No reports generated yet.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Analysis ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($reports as $report)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $report->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                #{{ $report->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $report->result === 'positive' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                    {{ ucfirst($report->result) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if(!$report->report_generated)
                                                    <form action="{{ route('reports.generate', $report) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="text-blue-600 hover:text-blue-900">
                                                            Generate Report
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('reports.show', $report) }}" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                                    <a href="{{ route('reports.download', $report) }}" 
                                                    class="text-green-600 hover:text-green-900">Download PDF</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $reports->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>