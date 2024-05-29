@extends('layouts.main')

@section('content')
    <div class="container mt-8" id="app">
        <h2>@{{ progress }}</h2>
        <hr/>
        <h5>@{{ pageTitle }}</h5>
        <hr/>
        <div class="progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                    role="progressbar" 
                    :aria-valuenow="progressPercentage" 
                    aria-valuemin="0" 
                    aria-valuemax="100" 
                    :style="`width: ${progressPercentage}%;`">
                @{{ progressPercentage }}%
            </div>
        </div>
    </div>
@endsection
@section('styles')
    <style>
        body {
            font-family: 'Nunito', sans-serif;
        }
    </style>
@endsection
@section('scripts')
    <!-- development version -->
    <script src="https://unpkg.com/vue@3"></script>
    <!-- production version -->
    <!--<script src="https://unpkg.com/vue@3.2.22/dist/vue.global.prod.js"></script>-->
        
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" 
            integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" 
            crossorigin="anonymous"></script>
    <script type="text/javascript">
        const app = {
            data(){
                return {
                    progress: 'Welcome to progress page',
                    pageTitle: 'Progress of Uploads',
                    progressPercentage: 0,
                    params: {
                        id: null
                    }
                }
            },
            methods: {
                checkIfIdPresent() {
                    const urlSearchParams = new URLSearchParams(window.location.search);
                    const params = Object.fromEntries(urlSearchParams.entries());
                    
                    //console.log(params);
                    if(params.id) {
                        this.params.id = params.id;
                    }
                },
                getUploadProgress() {
                    let self = this;
                    self.checkIfIdPresent();
                    
                    /**
                     * Ez egy intervallum, amelyet arra használnak, 
                     * hogy rendszeresen lekérjék a köteg haladását a szerverről. 
                     * Megszerezze a haladást, majd meghívja azt a funkciót, 
                     * amely felelős a felhasználói felület frissítéséért a haladással.
                     */
                    let progressResponse = setInterval(() => {

                        /**
                         * Küldj egy GET kérést a /progress/data végpontnak annak aktuális 
                         * haladásának lekérése érdekében a megadott azonosítóval rendelkező 
                         * feltöltéshez. Ha nincs megadott azonosító, akkor az utoljára 
                         * feltöltött azonosító lesz használva.
                         */
                        axios.get('/progress/data', {
                            /**
                             * A kérés paraméterei. 
                             * Ha egy azonosító van megadva az URL lekérdezési karakterláncában, 
                             * azt fogjuk használni. Egyébként az utolsó feltöltött azonosítót 
                             * fogjuk használni.
                             */
                            params: {
                                id: self.params.id 
                                    ? self.params.id 
                                    : "{{ session()->get('lastBatchId') }}"
                            }
                        }).then((response) => {
                            console.log('response.data', response.data);

                            /**
                             * A kötegben lévő összes munkák száma.
                             *
                             * @type {number}
                             */
                            let totalJobs = parseInt(response.data.total_jobs);

                            /**
                             * Azoknak a munkáknak a száma, amelyek még függőben vannak.
                             *
                             * @type {number}
                             */
                            let pendingJobs = parseInt(response.data.pending_jobs);
                            
                            /**
                             * A befejezett munkák száma.
                             *
                             * totalJobs a kötegben lévő összes munkát jelenti.
                             * pendingJobs a még folyamatban lévő munkák számát jelenti.
                             * Tehát ahhoz, hogy megkapjuk a befejezett munkák számát, 
                             * vonjuk ki a pendingJobs értékét a totalJobs értékéből. 
                             * Ez megadja nekünk az eddig befejezett munkák számát.
                             * @type {number}
                             */
                            let completedJobs = totalJobs - pendingJobs;
                                
                            /**
                             * Ha nincs több függőben lévő munka, 
                             * állítsd a haladási százalékot 100-ra.
                             *
                             * @type {number}
                             */
                            if( pendingJobs === 0 ) {
                                /**
                                 * Ha nincs több függőben lévő munka, 
                                 * állítsd a haladási százalékot 100-ra.
                                 *
                                 * @type {number}
                                 */
                                /**
                                 * @description Ez a kódblokk akkor fut le, 
                                 *              amikor nincsenek több függőben lévő munkák. 
                                 *              A haladási százalékot 100-ra állítja.
                                 *
                                 * @param {number} pendingJobs A függőben lévő munkák száma.
                                 * @param {number} totalJobs A kötegben lévő összes munkák száma.
                                 * @param {number} completedJobs A befejezett munkák száma.
                                 */
                                self.progressPercentage = 100;
                            } else {
                                /**
                                 * Számítsd ki a haladási százalékot úgy, hogy elosztod 
                                 * a befejezett munkák számát a teljes munkák számával, 
                                 * majd megszorzod 100-zal.
                                 *
                                 * @type {number}
                                 */
                                self.progressPercentage = (
                                    parseInt(completedJobs) / parseInt(totalJobs)
                                ) * 100;
                            }

                            /**
                             * Ha a haladási százalék 100 vagy annál több, állítsd le a frissítési hurokot. 
                             * Ez azért történik, hogy elkerüljük a szükségtelen kéréseket a szerver felé.
                             *
                             * @type {boolean}
                             */
                            if( parseInt(self.progressPercentage) >= 100 ) {
                                /**
                                 * @description Állítsd le a frissítési hurokot, ha a haladási százalék 100 vagy annál nagyobb.
                                 */
                                clearInterval(progressResponse);
                            }
                        });
                    }, 1000);
                }
            },
            created(){
                /**
                 * @description Get the upload progress and update the progress bar.
                 */
                this.getUploadProgress();
            }
        };

        /**
         * @description Create the Vue app and mount it to the #app element.
         *
         * @param {Object} app The Vue app instance.
         */
        Vue.createApp(app).mount('#app');

    </script>
@endsection
