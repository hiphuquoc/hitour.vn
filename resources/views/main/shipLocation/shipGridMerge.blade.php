@php
    $flag = false;
    foreach($list as $ship) {
        if(!empty($ship->prices[0]->price_adult)) {
            $flag = true;
            break;
        }
    }
@endphp

<div class="shipGrid">
    @if($flag==true)
        @foreach($list as $ship)
            @php
                $arrayBrandShip         = [];
                foreach($ship->prices as $price){
                    if(!in_array($price->partner->name, $arrayBrandShip)) $arrayBrandShip[] = $price->partner->name;
                }
            @endphp
            @if(!empty($ship->prices[0]->price_adult))
                <div class="shipGrid_item">
                    <div class="shipGrid_item_image">
                        <a href="/{{ $ship->seo->slug_full ?? null }}" title="{{ $ship->name ?? $ship->seo->title ?? $ship->seo->seo_title ?? null }}">
                            <img src="{{ config('main.svg.loading_main') }}" data-src="{{ $ship->seo->image_small ?? $ship->seo->image ?? config('admin.images.default_750x460') }}" alt="{{ $ship->name ?? $ship->seo->title ?? $ship->seo->seo_title ?? null }}" title="{{ $ship->name ?? $ship->seo->title ?? $ship->seo->seo_title ?? null }}" />
                        </a>
                        <div class="shipGrid_item_image_left">{{ !empty($ship->prices[0]->times[0]->time_move) ? \App\Helpers\Time::convertMkToTimeMove($ship->prices[0]->times[0]->time_move) : null }}</div>
                        <div class="shipGrid_item_image_bottom">{{ implode(', ', $arrayBrandShip) }}</div>
                    </div>
                    <div class="shipGrid_item_content">
                        <div class="shipGrid_item_content_title maxLine_1">
                        <a href="/{{ $ship->seo->slug_full ?? null }}" title="{{ $ship->name ?? $ship->seo->title ?? null }}">
                            @if(!empty($shipHeading)&&$shipHeading=='h3')
                                <h3>{{ $ship->name ?? $ship->seo->title ?? null }}</h3>
                            @else 
                                <h2>{{ $ship->name ?? $ship->seo->title ?? null }}</h2>
                            @endif
                        </a>
                        </div>
                        <div class="shipGrid_item_content_table">
                        <div class="shipGrid_item_content_table_row" style="align-items:center !important;">
                            <div class="shipGrid_item_content_table_row__dp maxLine_1" style="flex:unset !important;">
                                C???ng {{ $ship->departure->district->district_name ?? $ship->departure->province->province_name ?? 'kh??ng r??' }}
                            </div>
                            <div style="text-align:center;flex: 0 0 40px;">
                                <i class="fas fa-exchange-alt" style="vertical-align:middle;"></i>
                            </div>
                            <div class="shipGrid_item_content_table_row__dp maxLine_1">
                                C???ng {{ $ship->location->district->district_name ?? $ship->location->province->province_name ?? 'kh??ng r??' }}
                            </div>
                        </div>
                        @php
                            /* filter */
                            $arrayDeparture     = [];
                            $arrayPrice         = [
                                'price_adult'   => [], 
                                'price_child'   => [], 
                                'price_old'     => [], 
                                'price_vip'     => []
                            ];
                            foreach($ship->prices as $price){
                                /* x??y d???ng m???ng price */
                                if(!in_array($price->price_adult, $arrayPrice['price_adult'])) {
                                    $arrayPrice['price_adult'][] = $price->price_adult;
                                    sort($arrayPrice['price_adult']);
                                }
                                if(!in_array($price->price_child, $arrayPrice['price_child'])) {
                                    $arrayPrice['price_child'][] = $price->price_child;
                                    sort($arrayPrice['price_child']);
                                }
                                if(!in_array($price->price_old, $arrayPrice['price_old'])) {
                                    $arrayPrice['price_old'][] = $price->price_old;
                                    sort($arrayPrice['price_old']);
                                }
                                if(!in_array($price->price_vip, $arrayPrice['price_vip'])) {
                                    if(!empty($price->price_vip)) $arrayPrice['price_vip'][] = $price->price_vip;
                                    sort($arrayPrice['price_vip']);
                                }
                                /* x??y d???ng m???ng time */
                                foreach($price->times as $time){
                                    $arrayDeparture[$time->ship_from_sort.'-'.$time->ship_to_sort][] = $time->time_departure;
                                    /* s???p x???p m???i l???n th??m v??o m???ng */
                                    sort($arrayDeparture[$time->ship_from_sort.'-'.$time->ship_to_sort]);
                                }
                            }
                        @endphp
                        @foreach($arrayDeparture as $key => $value)
                            <div class="shipGrid_item_content_table_row" style="width:100%;{{ $loop->first ? 'margin-top:0.5rem' : null }}">
                                <div class="maxLine_1">
                                    Ch???ng {{ $key }}
                                </div>
                                <div class="maxLine_1" style="color:#003B7B;">
                                    @foreach(array_unique($value) as $v)
                                        <span style="font-weight:bold;">{{ $v }}</span>{{ !$loop->last ? ' | ' : null }}
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <div class="shipGrid_item_content_table_row" style="margin-top:0.5rem;">
                            <div>
                                Ng?????i l???n: 
                            </div>
                            <div>
                                @if(count($arrayPrice['price_adult'])>1)
                                    <span class="text-price_500">{{ number_format($arrayPrice['price_adult'][0]) }} - {{ number_format(end($arrayPrice['price_adult'])).config('main.unit_currency') }}</span> /v??
                                @else
                                    <span class="text-price_500">{!! !empty($arrayPrice['price_adult'][0]) ? number_format($arrayPrice['price_adult'][0]).config('main.unit_currency').'</span> /v??' : '-' !!}
                                @endif
                            </div>
                        </div>
                        <div class="shipGrid_item_content_table_row" style="margin-top:0.15rem;">
                            <div>
                                Tr??? em 6-11: 
                            </div>
                            <div>
                                @if(count($arrayPrice['price_child'])>1)
                                    <span class="text-price_500">{{ number_format($arrayPrice['price_child'][0]) }} - {{ number_format(end($arrayPrice['price_child'])).config('main.unit_currency') }}</span> /v??
                                @else
                                    <span class="text-price_500">{!! !empty($arrayPrice['price_child'][0]) ? number_format($arrayPrice['price_child'][0]).config('main.unit_currency').'</span> /v??' : '-' !!}
                                @endif
                            </div>
                        </div>
                        <div class="shipGrid_item_content_table_row" style="margin-top:0.15rem;">
                            <div>
                                Tr??n 60: 
                            </div>
                            <div>
                                @if(count($arrayPrice['price_old'])>1)
                                    <span class="text-price_500">{{ number_format($arrayPrice['price_old'][0]) }} - {{ number_format(end($arrayPrice['price_old'])).config('main.unit_currency') }}</span> /v??
                                @else
                                    <span class="text-price_500">{!! !empty($arrayPrice['price_old'][0]) ? number_format($arrayPrice['price_old'][0]).config('main.unit_currency').'</span> /v??' : '-' !!}
                                @endif
                            </div>
                        </div>
                        <div class="shipGrid_item_content_table_row" style="margin-top:0.15rem;">
                            <div>
                                V?? Vip: 
                            </div>
                            <div>
                                @if(count($arrayPrice['price_vip'])>1)
                                    <span class="text-price_500">{{ number_format($arrayPrice['price_vip'][0]) }} - {{ number_format(end($arrayPrice['price_vip'])).config('main.unit_currency') }}</span> /v??
                                @else
                                    <span class="text-price_500">{!! !empty($arrayPrice['price_vip'][0]) ? number_format($arrayPrice['price_vip'][0]).config('main.unit_currency').'</span> /v??' : 'Kh??ng c??<sup></sup>' !!}
                                @endif
                            </div>
                        </div>
                        </div>
                    </div>
                    <div class="shipGrid_item_btn">
                        <a href="{{ route('main.shipBooking.form', ['ship_port_departure_id' => $ship->portDeparture->id, 'ship_port_location_id' => $ship->portLocation->id]) }}" title="?????t v?? t??u {{ $ship->name ?? $ship->seo->title ?? null }}" style="border-radius:0 0 0 5px;">
                            <i class="far fa-edit"></i>?????t v??
                        </a>
                        <a href="/{{ $ship->seo->slug_full ?? null }}" title="Xem chi ti???t t??u cao t???c {{ $ship->name ?? $ship->seo->title ?? null }}" style="border-radius:0 0 5px 0;">
                            <i class="fas fa-external-link-alt"></i>Xem chi ti???t
                        </a>
                    </div>
                </div>
            @endif
            @php
                if(!empty($limit)&&($loop->index+1)==$limit) break;
            @endphp
        @endforeach
    @else 
        <div style="color:rgb(0,123,255);">Hi???n kh??ng c?? l???ch t??u ??i {{ $item->display_name ?? null }} tr??n h??? th???ng Hitour!</div>
    @endif
 </div>
 @if(!empty($limit)&&$list->count()>$limit)
    <div class="viewMore">
        <a href="/{{ $link ?? null }}" title="Xem th??m"><i class="fa-solid fa-arrow-down-long"></i>Xem th??m</a>
    </div>
@endif