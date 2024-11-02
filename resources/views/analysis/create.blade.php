<x-app-layout>
    @section('title', 'Create New Analysis')

    <div class="max-w-7xl mx-auto">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <form action="{{ route('analysis.store') }}" method="POST" enctype="multipart/form-data" 
                      id="analysis-form" class="space-y-6">
                    @csrf
    
                                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <!-- File Upload Area -->
                      <div class="col-span-1">
                          <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 cursor-pointer"
                               id="upload-area">
                              <input type="file" name="image" id="image-input" class="hidden" accept="image/*">
                              <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                  <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                              </svg>
                              <div class="mt-4">
                                  <span class="text-sm font-medium text-blue-600">Upload a file</span>
                                  <p class="text-xs text-gray-500">PNG, JPG up to 5MB</p>
                              </div>
                          </div>
                      </div>
    
                      <!-- Image Preview -->
                      <div class="col-span-1">
                          <div class="bg-gray-50 rounded-lg p-4 h-full flex items-center justify-center">
                              <img id="image-preview" src="" alt="" class="max-h-48 hidden">
                              <p id="no-preview" class="text-gray-400">No image selected</p>
                          </div>
                      </div>
                  </div>
    
                  <div class="flex justify-end space-x-4">
                      <button type="button" 
                              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                          Cancel
                      </button>
                      <button type="submit" 
                              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                          Analyze Image
                      </button>
                  </div>
              </form>
          </div>
      </div>
    </div>
    
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('upload-area');
            const imageInput = document.getElementById('image-input');
            const imagePreview = document.getElementById('image-preview');
            const noPreview = document.getElementById('no-preview');
            const form = document.getElementById('analysis-form');

            uploadArea.addEventListener('click', () => imageInput.click());

            imageInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        noPreview.classList.add('hidden');
                    }
                    
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';
                
                try {
                    const formData = new FormData(form);
                    
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Server error occurred');
                    }

                    if (result.success) {
                        window.location.href = result.redirect_url;
                    } else {
                        throw new Error(result.message || 'Analysis failed');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message || 'An error occurred. Please try again.');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Analyze Image';
                }
            });
            }); 
        </script>
    @endpush
</x-app-layout>