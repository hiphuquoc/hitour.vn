@extends('main.layouts.main')
@push('head-custom')
<!-- ===== START:: SCHEMA ===== -->
@php
    $dataSchema = $item->seo ?? null;
@endphp

<!-- STRAT:: Title - Description - Social -->
@include('main.schema.social', ['data' => $dataSchema])
<!-- END:: Title - Description - Social -->

<!-- STRAT:: Organization Schema -->
@include('main.schema.organization')
<!-- END:: Organization Schema -->

<!-- STRAT:: Article Schema -->
@include('main.schema.article', ['data' => $dataSchema])
<!-- END:: Article Schema -->

<!-- STRAT:: Article Schema -->
@include('main.schema.creativeworkseries', ['data' => $dataSchema])
<!-- END:: Article Schema -->

<!-- STRAT:: Product Schema -->
@php
    $arrayPrice = [];
    foreach($item->tourCountries as $tourCountry) {
        foreach($tourCountry->tours as $tour) if(!empty($tour->infoTourForeign->price_show)) $arrayPrice[] = $tour->infoTourForeign->price_show;
    }
    $highPrice  = !empty($arrayPrice) ? max($arrayPrice) : 5000000;
    $lowPrice   = !empty($arrayPrice) ? min($arrayPrice) : 3000000;
@endphp
@include('main.schema.product', ['data' => $dataSchema, 'files' => $item->files, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice])
<!-- END:: Product Schema -->

<!-- STRAT:: Article Schema -->
@include('main.schema.breadcrumb', ['data' => $breadcrumb])
<!-- END:: Article Schema -->

<!-- STRAT:: FAQ Schema -->
@include('main.schema.faq', ['data' => $item->questions])
<!-- END:: FAQ Schema -->

@php
    $dataList       = new \Illuminate\Support\Collection();
    if(!empty($item->tourCountries)&&$item->tourCountries->isNotEmpty()){
        foreach($item->tourCountries as $tourCountry){
            foreach($tourCountry->tours as $tour){
                if(!empty($tour->infoTourForeign)) $dataList[] = $tour->infoTourForeign;
            }
        }
    }
@endphp
<!-- STRAT:: Article Schema -->
@include('main.schema.itemlist', ['data' => $dataList])
<!-- END:: Article Schema -->

<!-- ===== END:: SCHEMA ===== -->
@endpush
@section('content')

    @include('main.form.sortBooking', [
        'item'      => $item,
        'active'    => 'tour'
    ])

    @include('main.snippets.breadcrumb')

    <div class="pageContent">

            <!-- Gi???i thi???u Tour du l???ch -->
            <div class="sectionBox">
                <div class="container">
                    <!-- title -->
                    <h1 class="titlePage">Tour {{ $item->display_name }} - Gi???i thi???u Tour du l???ch {{ $item->display_name }}</h1>
                    <!-- rating -->
                    @include('main.template.rating', compact('item'))
                    <!-- content -->
                    @if(!empty($content))
                        <div id="js_showHideFullContent_content" class="contentBox maxLine_4">
                            {!! $content !!}
                        </div>
                        <div class="viewMore" style="margin-top:1.5rem;">
                            <div onClick="showHideFullContent(this, 'maxLine_4');">
                                <i class="fa-solid fa-arrow-down-long"></i>?????c th??m
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tour box -->
            <div class="sectionBox backgroundPrimaryGradiend">
                <div class="container">
                    <h2 class="sectionBox_title">Tour {{ $item->display_name }} - Danh s??ch Tour du l???ch {{ $item->display_name ?? null }} ch???t l?????ng</h2>
                    <p>T???ng h???p c??c ch????ng tr??nh <strong>Tour {{ $item->display_name ?? null }}</strong> ??a d???ng, ch???t l?????ng h??ng ?????u ???????c cung c???p v?? ?????m b???o b???i Hitour c??ng h??? th???ng ?????i t??c.</p>
                    {{-- @include('main.tourLocation.filterBox') --}}
                    @php
                        $dataTours              = new \Illuminate\Support\Collection();
                        foreach($item->tourCountries as $tourCountry){
                            foreach($tourCountry->tours as $tour) if(!empty($tour->infoTourForeign)) $dataTours[] = $tour->infoTourForeign;
                        }
                    @endphp
                    @if(!empty($dataTours)&&$dataTours->isNotEmpty())
                        @include('main.tourLocation.tourGrid', ['list' => $dataTours])
                    @else 
                        <div style="color:rgb(0,123,255);">C??c ch????ng tr??nh <strong>Tour {{ $item->display_name ?? null }}</strong> ??ang ???????c Hitour c???p nh???t v?? s??? s???m gi???i thi???u ?????n Qu?? kh??ch trong th???i gian t???i!</div>
                    @endif
                </div>
            </div>
            

            <!-- START:: Video -->
            @include('main.tourLocation.videoBox', [
                'item'  => $item,
                'title' => 'Video Tour du l???ch '.$item->display_name
            ])
            <!-- END:: Video -->

            <!-- H?????ng d???n ?????t Tour -->
            @include('main.tourLocation.guideBookTour', ['title' => 'Quy tr??nh ?????t Tour '.$item->display_name.' v?? S??? d???ng d???ch v???'])

            <!-- V?? m??y bay -->
            @php
                $dataAirs               = new \Illuminate\Support\Collection();
                foreach($item->airLocations as $airLocation){
                    foreach($airLocation->infoAirLocation->airs as $air){
                        $dataAirs[]     = $air;
                    }
                }
            @endphp
            @if(!empty($dataAirs)&&$dataAirs->isNotEmpty())
                <div class="sectionBox">
                    <div class="container">
                        <h2 class="sectionBox_title">V?? m??y bay ??i {{ $item->display_name ?? null }}</h2>
                        <p>????? ?????n ???????c {{ $item->display_name ?? null }} nhanh ch??ng, an to??n v?? ti???n l???i t???t nh???t b???n n??n di chuy???n b???ng m??y bay. Th??ng tin chi ti???t c??c <strong>chuy???n bay ?????n {{ $item->display_name ?? null }}</strong> b???n c?? th??? tham kh???o b??n d?????i</p>
                        @include('main.tourLocation.airGrid', [
                            'list'          => $dataAirs, 
                            'limit'         => 3, 
                            'link'          => $item->airLocations[0]->infoAirLocation->seo->slug_full,
                            'itemHeading'   => 'h3'
                        ])
                    </div>
                </div>
            @endif

            <!-- V?? vui ch??i & gi???i tr?? -->
            @if(!empty($item->serviceLocations[0]->infoServiceLocation))
                <div class="sectionBox">
                    <div class="container">
                        <h2 class="sectionBox_title">V?? vui ch??i t???i {{ $item->display_name ?? null }}</h2>
                        <p>Ngo??i c??c <strong>ch????ng tr??nh Tour {{ $item->display_name ?? null }}</strong> b???n c??ng c?? th??? tham kh???o th??m c??c <strong>ho???t ?????ng vui ch??i, gi???i tr?? kh??c t???i {{ $item->display_name ?? null }}</strong>. ????y l?? c??c ch????ng tr??nh ?????c bi???t b???n c?? th??? tham gia ????? b?? ?????p th???i gian t??? t??c trong <strong>ch????ng tr??nh Tour</strong> v?? ch???c ch???n s??? mang ?????n cho b???n nhi???u tr???i nghi???m th?? v???.</p>
                        @include('main.tourLocation.serviceGrid', [
                            'list'          => $item->serviceLocations,
                            'itemHeading'   => 'h3'
                        ])
                    </div>
                </div>
            @endif

            <!-- C???m nang du l???ch -->
            @if(!empty($item->guides[0]->infoGuide))
                <div class="sectionBox withBorder">
                    <div class="container">
                        <h2 class="sectionBox_title">C???m nang du l???ch {{ $item->display_name ?? null }}</h2>
                        <p>N???u c??c ch????ng tr??nh <strong>Tour du l???ch {{ $item->display_name ?? null }}</strong> c???a Hitour kh??ng ????p ???ng ???????c nhu c???u c???a b???n ho???c l?? ng?????i ??u th??ch du l???ch t??? t??c,... B???n c?? th??? tham kh???o <strong>C???m nang du l???ch</strong> b??n d?????i ????? c?? ?????y ????? th??ng tin, t??? do l??n k??? ho???ch, s???p x???p l???ch tr??nh cho chuy???n ??i c???a m??nh ???????c chu ????o nh???t.</p>
                        <div class="guideList">
                            @foreach($item->guides as $guide)
                                <div class="guideList_item">
                                    <i class="fa-solid fa-angles-right"></i>Xem th??m <a href="/{{ $guide->infoGuide->seo->slug_full }}" title="{{ $guide->infoGuide->name }}">{{ $guide->infoGuide->name }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- faq -->
            @if(!empty($item->questions)&&$item->questions->isNotEmpty())
                <div class="sectionBox withBorder">
                    <div class="container">
                        <h2 class="sectionBox_title">C??u h???i th?????ng g???p v??? Tour {{ $item->display_name ?? null }}</h2>
                        @include('main.snippets.faq', ['list' => $item->questions, 'title' => $item->name])
                    </div>
                </div>
            @endif
    </div>

    
    
@endsection
@push('scripts-custom')
    <script type="text/javascript">
        $(window).on('load', function () {
            setupSlick();
            $(window).resize(function(){
                setupSlick();
            })

            $('.sliderHome').slick({
                dots: true,
                arrows: true,
                autoplay: true,
                infinite: true,
                autoplaySpeed: 5000,
                lazyLoad: 'ondemand',
                responsive: [
                    {
                        breakpoint: 567,
                        settings: {
                            arrows: false,
                        }
                    }
                ]
            });

            function setupSlick(){
                setTimeout(function(){
                    $('.sliderHome .slick-prev').html('<i class="fa-solid fa-arrow-left-long"></i>');
                    $('.sliderHome .slick-next').html('<i class="fa-solid fa-arrow-right-long"></i>');
                    $('.sliderHome .slick-dots button').html('');
                }, 0);
            }
        });
    </script>
@endpush