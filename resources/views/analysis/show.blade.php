<x-app-layout>
  <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
              <div class="p-6">
                  <!-- Header -->
                  <div class="border-b border-gray-200 pb-4 mb-4">
                      <h2 class="text-2xl font-semibold text-gray-800">Analysis Results #{{ $analysis->id }}</h2>
                      <p class="text-sm text-gray-500">
                          Analyzed on {{ $analysis->processed_at ? $analysis->processed_at->format('M d, Y H:i:s') : 'Pending' }}
                      </p>
                  </div>

                  <!-- Status Banner -->
                  <div class="mb-6">
                      @if($analysis->status === 'completed')
                          <div class="bg-green-50 border-l-4 border-green-400 p-4">
                              <div class="flex">
                                  <div class="flex-shrink-0">
                                      <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                      </svg>
                                  </div>
                                  <div class="ml-3">
                                      <p class="text-sm text-green-700">
                                          Analysis completed successfully
                                      </p>
                                  </div>
                              </div>
                          </div>
                      @elseif($analysis->status === 'failed')
                          <div class="bg-red-50 border-l-4 border-red-400 p-4">
                              <div class="flex">
                                  <div class="flex-shrink-0">
                                      <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                      </svg>
                                  </div>
                                  <div class="ml-3">
                                      <p class="text-sm text-red-700">
                                          Analysis failed: {{ $analysis->error_message }}
                                      </p>
                                  </div>
                              </div>
                          </div>
                      @else
                          <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                              <div class="flex">
                                  <div class="flex-shrink-0">
                                      <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                      </svg>
                                  </div>
                                  <div class="ml-3">
                                      <p class="text-sm text-blue-700">
                                          Analysis in progress...
                                      </p>
                                  </div>
                              </div>
                          </div>
                      @endif
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <!-- Original Image -->
                      <div class="bg-gray-50 rounded-lg p-4">
                          <h3 class="text-lg font-medium text-gray-900 mb-4">Analyzed Image</h3>
                          <img src="{{ Storage::url($analysis->image_path) }}" 
                               alt="Blood Smear Sample" 
                               class="w-full h-auto rounded-lg">
                      </div>

                      <!-- Analysis Results -->
                      <div>
                          <h3 class="text-lg font-medium text-gray-900 mb-4">Detection Results</h3>
                          @if($analysis->status === 'completed' && $analysis->result_data)
                              @php
                                  $resultData = is_array($analysis->result_data) ? $analysis->result_data : json_decode($analysis->result_data, true);
                              @endphp
                              
                              <div class="space-y-4">
                                  <!-- Confidence Score -->
                                <div>
                                  <div class="flex justify-between mb-1">
                                      <span class="text-sm font-medium text-gray-700">Confidence Score</span>
                                      <span class="text-sm font-medium text-gray-700">{{ $resultData['confidence'] ?? 'N/A' }}%</span>
                                  </div>
                                  <div class="w-full bg-gray-200 rounded-full h-2">
                                      <div class="bg-blue-600 h-2 rounded-full" 
                                          style="width: {{ $resultData['confidence'] ?? 0 }}%"></div>
                                  </div>
                                </div>

                                  <!-- Detection Status -->
                                  <div class="bg-gray-50 rounded-lg p-4">
                                      <div class="flex items-center">
                                          <div class="flex-shrink-0">
                                            @if(array_key_exists('detection', $resultData) && $resultData['detection'])
                                                <span class="h-4 w-4 rounded-full bg-red-400 flex items-center justify-center">
                                                    <span class="h-2 w-2 rounded-full bg-red-600"></span>
                                                </span>
                                            @else
                                                <span class="h-4 w-4 rounded-full bg-green-400 flex items-center justify-center">
                                                    <span class="h-2 w-2 rounded-full bg-green-600"></span>
                                                </span>
                                            @endif
                                          </div>
                                          <div class="ml-3">
                                            <h4 class="text-sm font-medium text-gray-900">
                                                @if(array_key_exists('detection', $resultData) && $resultData['detection'])
                                                    Malaria Parasites Detected
                                                @else
                                                    No Malaria Parasites Detected
                                                @endif
                                            </h4>
                                        </div>
                                      </div>
                                  </div>

                                  <!-- Additional Details -->
                                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                      <div>
                                          <span class="text-sm font-medium text-gray-500">Parasite Type:</span>
                                          <p class="mt-1 text-sm text-gray-900">
                                              @if(array_key_exists('parasiteType', $resultData))
                                                  {{ $resultData['parasiteType'] }}
                                              @else
                                                  N/A
                                              @endif
                                          </p>
                                      </div>
                                      <div>
                                          <span class="text-sm font-medium text-gray-500">Stage:</span>
                                          <p class="mt-1 text-sm text-gray-900">
                                              @if(array_key_exists('stage', $resultData))
                                                  {{ $resultData['stage'] }}
                                              @else
                                                  N/A
                                              @endif
                                          </p>
                                      </div>
                                      <div>
                                          <span class="text-sm font-medium text-gray-500">Findings:</span>
                                          <p class="mt-1 text-sm text-gray-900">
                                              @if(array_key_exists('findings', $resultData))
                                                  {{ $resultData['findings'] }}
                                              @else
                                                  N/A
                                              @endif
                                          </p>
                                      </div>
                                  </div>

                                  <!-- Recommendations -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                      <h4 class="text-sm font-medium text-gray-900 mb-2">Recommendations:</h4>
                                      <ul class="list-disc list-inside space-y-1">
                                          @if(array_key_exists('recommendations', $resultData))
                                              @foreach($resultData['recommendations'] as $recommendation)
                                                  <li class="text-sm text-gray-600">{{ $recommendation }}</li>
                                              @endforeach
                                          @else
                                              <li class="text-sm text-gray-600">No recommendations available.</li>
                                          @endif
                                      </ul>
                                    </div>
                              </div>
                          @else
                              <div class="text-gray-500 italic">
                                  No results available yet.
                              </div>
                          @endif
                      </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="mt-6 flex justify-end space-x-4">
                      <a href="{{ route('analysis.create') }}" 
                         class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                          New Analysis
                      </a>
                      @if($analysis->status === 'completed')
                          <button type="button"
                                  onclick="window.print()"
                                  class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                              Print Results
                          </button>
                      @endif
                  </div>
              </div>
          </div>
      </div>
  </div>
</x-app-layout>