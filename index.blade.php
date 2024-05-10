@extends('assembly.layouts.app')

@section('title')
    <?=translate('bills')?>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card" data-color="success">
            <div class="card-header">
                <div class="card__title--container">
                    <div class="card__title-icon">
                        <ion-icon name="grid-outline"></ion-icon>
                    </div>
                    <h3 class="card__title"><?=translate('bills')?></h3>
                </div>
                <div class="card__button--container">
                    <a href="{{url(Request::url())}}" class="card__btn">
                        <ion-icon name="refresh-outline"></ion-icon>
                    </a>
                </div>
            </div>

            <div class="card__body">
                <table id="example" class="table table-bordered table-striped display nowrap">
                    <thead>
                        <tr>
                            <th style="width: 6.67%;"><?=translate('sl')?></th>
                            <th style="width: 45.33%;"><?=translate('name')?></th>
                            <th style="width: 10%;"><?=translate('files')?></th>
                            <th style="width: 10%;"><?=translate('size')?></th>
                            <th style="width: 20%;"><?=translate('created_at')?></th>
                            <th style="width: 8%;" class="no-sort"><?=translate('action')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; ?>
                        @if(isset($data))
                            @foreach($data as $row)
                                <tr>
                                    <td>{!! $count !!}</td>
                                    <td>
                                        <a href="<?=route('assembly.business', ['id'=>$row->id])?>" style="display: flex; align-items: center;gap: 0.25rem; color: black;">
                                            <ion-icon name="folder-open" style="font-size: 1.5rem; color: #ffbd2e;"></ion-icon>
                                            {!! $row->name !!}
                                        </a>
                                    </td>
                                    <td>
                                        {!! $row->countFiles() !!}
                                        @if($row->countFiles() == 1) <?=translate('file')?> @else <?=translate('files')?> @endif
                                    </td>
                                    <td></td>
                                    <td>{!! \Carbon\Carbon::parse($row->created_at)->format('F jS\, Y') !!}</td>
                                    <td>
                                        
                                    </td>
                                </tr>
                                <?php $count++; ?>
                            @endforeach
                        @endif
                        @if(isset($files))
                            @foreach($files as $file)
                                <tr>
                                    <td>{!! $count++ !!}</td>
                                    <td>
                                        <a
                                        onclick="retrievePDFFromIndexedDB('{{$file->name }}', '{{asset($file->path)}}')"  
                                        style="display: flex; align-items: center;gap: 0.25rem; color: black;">
                                            <ion-icon name="document" style="font-size: 1.5rem; color: #25b003;"></ion-icon>
                                            {!! $file->name !!}
                                        </a>
                                    </td>
                                    <td>-</td>
                                    <td>{{convert_filesize($file->size)}}</td>
                                    <td>{{\Carbon\Carbon::parse($file->date)->format('F jS\, Y \a\t h:i A')}}</td>
                                    <td>

                                        <div class="action-btns">
                                            <a 
                                                class="action-btn icon view-btn view-btn" 
                                                title="<?=translate('preview')?>" 
                                                onclick="retrievePDFFromIndexedDB('{{$file->name }}', '{{asset($file->path)}}')"
                                                >
                                                <ion-icon name="eye-outline"  id="viewFileBtn"></ion-icon>
                                            </a>
                                            
                                            <a href="<?=asset($file->path)?>"  
                                                class="action-btn icon download-btn" 
                                                title="<?=translate('download')?>"  
                                                onclick="downloadFile('{{ asset($file->path) }}', '{{ $file->name }}')"
                                                download>
                                                <ion-icon name="cloud-download-outline"></ion-icon>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php $count++; ?>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection

@section('extra_scripts')
<script>
        (function($) {
            
        })(jQuery);
    </script>

<script>
    function downloadFile(url, filename) {
    fetch(url)
        .then(response => {
            // Convert the response to a Blob
            return response.blob().then(blob => {
                // Create an object containing the downloaded file data and the file name
                const downloadedFile = {
                    data: blob,
                    name: filename
                };
                savePDFToIndexedDB(downloadedFile.data, downloadedFile.name)
            });
        })
        .catch(error => {
            // Handle any errors
            console.error('Error downloading the file:', error);
        });
}


function savePDFToIndexedDB(pdfFile, fileName) {
    convertToBuffer(pdfFile, function (buffer) {
        var dbRequest = window.indexedDB.open('PDFDatabase', 1);

        dbRequest.onerror = function (event) {
            console.error("Error opening IndexedDB:", event.target.error);
        };

        dbRequest.onsuccess = function (event) {
            var db = event.target.result;
            var transaction = db.transaction(['PDFStore'], 'readwrite');
            var objectStore = transaction.objectStore('PDFStore');

            // Use filename as the key while storing in IndexedDB
            var request = objectStore.add({ file: buffer, file_name: fileName }); // Use filename as key

            request.onerror = function (event) {
                console.error("Error adding PDF to IndexedDB:", event.target.error);
            };

            request.onsuccess = function (event) {
                console.log("PDF added to IndexedDB successfully");
            };
        };

        dbRequest.onupgradeneeded = function (event) {
            var db = event.target.result;
            var objectStore = db.createObjectStore('PDFStore', { keyPath: 'id', autoIncrement: true });
            objectStore.createIndex('file_name', 'file_name', { unique: false }); // Create index on file_name
        };
    });
}

// This function remains unchanged
function convertToBuffer(file, callback) {
    var reader = new FileReader();
    reader.onload = function (event) {
        callback(event.target.result);
    };
    reader.readAsArrayBuffer(file);
}


function retrievePDFFromIndexedDB(fileName, filePath) {
            if (navigator.onLine) {
                window.open(filePath, '_blank');
            } else {
            var dbRequest = window.indexedDB.open('PDFDatabase', 1);

            dbRequest.onerror = function (event) {
                console.error("Error opening IndexedDB:", event.target.error);
            };

            dbRequest.onsuccess = function (event) {
                var db = event.target.result;
                var transaction = db.transaction(['PDFStore'], 'readonly');
                var objectStore = transaction.objectStore('PDFStore');


                // Open the index on 'file_name'
                var index = objectStore.index('file_name');

                // Retrieve PDF data using 'file_name' as key
                var getRequest = index.get(fileName);

                getRequest.onerror = function (event) {
                    console.error("Error retrieving PDF from IndexedDB:", event.target.error);
                };

                getRequest.onsuccess = function (event) {
                    var pdfData = event.target.result;
                    if (pdfData) {
                        // Convert PDF data to Blob object
                        var blob = new Blob([pdfData.file], { type: 'application/pdf' });
                        // Create a URL for the Blob object
                        var url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                    } else {
                        console.error("Retrieved PDF data is null or undefined");
                    }
                };
            };}
}



</script>
@endsection