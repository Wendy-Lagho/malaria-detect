<x-app-layout>

@section('title', 'Review Analysis')

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold text-gray-900">Review Analysis #{{ $analysis->id }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    This analysis requires manual review due to low confidence score ({{ number_format($analysis->confidence_score, 1) }}%)
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Image Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Blood Smear Image</h3>
                    <div class="border rounded-lg overflow-hidden">
                        <img src="{{ Storage::url($analysis->image_path) }}" 
                             alt="Blood Smear Analysis #{{ $analysis->id }}"
                             class="w-full h-auto">
                    </div>
                </div>

                <!-- Analysis Details -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">AI Analysis Results</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <dl class="divide-y divide-gray-200">
                            <div class="py-3 flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Initial Result</dt>
                                <dd class="text-sm text-gray-900">{{ ucfirst($analysis->result ?? 'Inconclusive') }}</dd>
                            </div>
                            <div class="py-3 flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Confidence Score</dt>
                                <dd class="text-sm text-gray-900">{{ number_format($analysis->confidence_score, 1) }}%</dd>
                            </div>
                            <div class="py-3 flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Parasite Type</dt>
                                <dd class="text-sm text-gray-900">{{ $analysis->result_data['parasiteType'] ?? 'Unknown' }}</dd>
                            </div>
                            <div class="py-3 flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Stage</dt>
                                <dd class="text-sm text-gray-900">{{ $analysis->result_data['stage'] ?? 'Unknown' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Review Form -->
                    <form action="{{ route('analyses.update', $analysis) }}" method="POST" class="mt-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Final Result
                                </label>
                                <select name="result" required
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="">Select result...</option>
                                    <option value="positive">Positive</option>
                                    <option value="negative">Negative</option>
                                    <option value="inconclusive">Still Inconclusive (Needs Further Review)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Notes
                                </label>
                                <textarea name="review_notes" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                          placeholder="Add any additional observations or notes..."></textarea>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <a href="{{ route('analyses.index') }}"
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancel
                                </a>
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Submit Review
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>