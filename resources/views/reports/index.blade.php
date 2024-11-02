<x-app-layout>
  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <!-- Statistics Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                  <div class="p-6">
                      <div class="text-sm font-medium text-gray-500">Total Reports</div>
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

          <!-- Reports Table -->
          <div class="bg-white overflow-hidden shadow-sm rounded-lg">
              <div class="p-6 bg-white border-b border-gray-200">
                  <h2 class="text-xl font-semibold text-gray-800 mb-4">Generated Reports</h2>

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
                                              <a href="{{ route('reports.show', $report) }}" 
                                                 class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                              <a href="{{ route('reports.download', $report) }}" 
                                                 class="text-green-600 hover:text-green-900">Download PDF</a>
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
