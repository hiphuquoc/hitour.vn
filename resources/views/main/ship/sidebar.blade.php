<div class="js_scrollFixed">
   <div class="callBookTour">
      @include('main.template.callbook', ['button' => 'Đặt vé tàu', 'flagButton' => true])
   </div>

   @if(!empty($item->location->tourLocations)&&$item->location->tourLocations->isNotEmpty())
   <div class="serviceRelatedSidebarBox" style="margin-top:1.5rem;">
      <div class="serviceRelatedSidebarBox_title">
         <h2>Chuyên mục liên quan</h2>
      </div>
      <div class="serviceRelatedSidebarBox_box">

         <!-- tour du lịch -->
         @foreach($item->location->tourLocations as $tourLocation)
            <a href="/{{ $tourLocation->infoTourLocation->seo->slug_full }}" class="serviceRelatedSidebarBox_box_item">
               <i class="fa-solid fa-person-hiking"></i><h3>{{ $tourLocation->infoTourLocation->name }}</h3>
            </a>
         @endforeach

         <!-- vé máy bay -->
         @foreach($item->location->tourLocations as $tourLocation)
            @if($tourLocation->infoTourLocation->airLocations->isNotEmpty())
               @foreach($tourLocation->infoTourLocation->airLocations as $airLocation)
                  <a href="/{{ $airLocation->infoAirLocation->seo->slug_full }}" class="serviceRelatedSidebarBox_box_item">
                     <i class="fa-solid fa-paper-plane"></i><h3>{{ $airLocation->infoAirLocation->name }}</h3>
                  </a>
               @endforeach
            @endif
         @endforeach

         <!-- vé vui chơi -->
         @foreach($item->location->tourLocations as $tourLocation)
            @if($tourLocation->infoTourLocation->serviceLocations->isNotEmpty())
               @foreach($tourLocation->infoTourLocation->serviceLocations as $serviceLocation)
                  <a href="/{{ $serviceLocation->infoServiceLocation->seo->slug_full }}" class="serviceRelatedSidebarBox_box_item">
                     <i class="fa-solid fa-star"></i><h3>{{ $serviceLocation->infoServiceLocation->name }}</h3>
                  </a>
               @endforeach
            @endif
         @endforeach

         <!-- cho thuê xe -->
         @foreach($item->location->tourLocations as $tourLocation)
            @if($tourLocation->infoTourLocation->carrentalLocations->isNotEmpty())
               @foreach($tourLocation->infoTourLocation->carrentalLocations as $carrentalLocation)
                  <a href="/{{ $carrentalLocation->infoCarrentalLocation->seo->slug_full }}" class="serviceRelatedSidebarBox_box_item">
                     <i class="fa-solid fa-car-side"></i><h3>{{ $carrentalLocation->infoCarrentalLocation->name }}</h3>
                  </a>
               @endforeach
            @endif
         @endforeach

         <!-- cẩm nang du lịch -->
         @foreach($item->location->tourLocations as $tourLocation)
            @if($tourLocation->infoTourLocation->guides->isNotEmpty())
               @foreach($tourLocation->infoTourLocation->guides as $guide)
                  <a href="/{{ $guide->infoGuide->seo->slug_full }}" class="serviceRelatedSidebarBox_box_item">
                     <i class="fa-solid fa-book"></i><h3>{{ $guide->infoGuide->name }}</h3>
                  </a>
               @endforeach
            @endif
         @endforeach

         {{-- <a href="#" class="serviceRelatedSidebarBox_box_item">
            <i class="fa-solid fa-building"></i><h3>Khách sạn Phú Quốc</h3>
         </a>
         <a href="#" class="serviceRelatedSidebarBox_box_item">
            <i class="fa-solid fa-plane-departure"></i><h3>Vé máy bay</h3>
         </a> --}}
      </div>
   </div>
   @endif

   <div id="js_autoLoadTocContentWithIcon_idWrite" class="tocContentTour customScrollBar-y" style="margin-top:1.5rem;">
      <!-- loadTocContent ajax -->
   </div>

</div>