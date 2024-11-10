<x-app-layout>

    @section('title', 'Past Analyses')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Total Analyses</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $statistics['total'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Positive Results</div>
                        <div class="mt-1 text-3xl font-semibold text-green-600">{{ $statistics['positive'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Negative Results</div>
                        <div class="mt-1 text-3xl font-semibold text-blue-600">{{ $statistics['negative'] }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">Needs Review</div>
                        <div class="mt-1 text-3xl font-semibold text-yellow-600">{{ $statistics['needs_review'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Analyses Table -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Past Analyses</h2>
                        <a href="{{ route('analysis.create') }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            New Analysis
                        </a>
                    </div>

                    @if($analyses->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500">No analyses found.</p>
                            <a href="{{ route('analysis.create') }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                Create your first analysis
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Result
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Confidence
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($analyses as $analysis)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $analysis->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $analysis->result === 'positive' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                    {{ ucfirst($analysis->result) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ number_format($analysis->confidence_score, 1) }}%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    @if($analysis->status === 'completed') bg-green-100, text-green-800,
                                                    @elseif($analysis->status === 'processing') bg-yellow-100, text-yellow-800,
                                                    @else bg-red-100, text-red-800
                                                    @endif">
                                                    {{ ucfirst($analysis->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('analysis.show', $analysis) }}" 
                                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                                    View
                                                </a>
                                                @if($analysis->report_generated)
                                                    <a href="{{ route('analysis.download-report', $analysis) }}" 
                                                       class="text-green-600 hover:text-green-900 mr-3">
                                                        Report
                                                    </a>
                                                @endif
                                                <button onclick="deleteAnalysis({{ $analysis->id }})" 
                                                        class="text-red-600 hover:text-red-900">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $analyses->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function deleteAnalysis(id) {
            if (confirm('Are you sure you want to delete this analysis?')) {
                fetch(`/analysis/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Failed to delete analysis');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the analysis');
                });
            }
        }
    </script>
    @endpush
</x-app-layout>