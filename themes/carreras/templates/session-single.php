<?php global $wp_query; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
  <head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style media="screen">
    .header { position: fixed; top: 0; left: 0; width: 100%; }
    .detail { padding: 20px; text-align: center; border: 1px solid #aaa; background: rgba(0, 0, 0, 0.05); }
    .map { height: 100vh; min-height: 500px; }
    </style>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <div class="detail col-12 col-xs-12 col-sm-4">Distancia recorrida: <b><?= get_post_meta($wp_query->post->ID, 'distance_km', true); ?></b> km</div>
        <div class="detail col-12 col-xs-12 col-sm-4">Duración: <b><?= get_post_meta($wp_query->post->ID, 'duration_minutes', true); ?></b> minutos</div>
        <div class="detail col-12 col-xs-12 col-sm-4">Velocidad media: <b><?= get_post_meta($wp_query->post->ID, 'average_speed_kmh', true); ?></b> km/h</div>
        <div id="map" class="map col-12 col-xs-12"></div>
      </div>
    </div>

    <script type="text/javascript">
    function initMap() {
      var coordinates = <?= json_encode(
        get_post_meta($wp_query->post->ID, 'coordinates', true) ?? []
      ); ?>.map(function(coordinate) {
        coordinate.lat = parseFloat(coordinate.latitude);
        coordinate.lng = parseFloat(coordinate.longitude);
        coordinate.speed = parseFloat(coordinate.speed);
        return coordinate;
      });

      var bounds = new google.maps.LatLngBounds();

      var infoWindow = new google.maps.InfoWindow();

      var map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: -34.397, lng: 150.644 },
        zoom: 8
      });

      <?php if ($wp_query->post->post_parent): ?>
        new google.maps.Polyline({
          path: <?= json_encode(
            get_post_meta($wp_query->post->post_parent, 'coordinates', true) ?? []
          ); ?>.map(function(coordinate) {
            coordinate.lat = parseFloat(coordinate.latitude);
            coordinate.lng = parseFloat(coordinate.longitude);
            return coordinate;
          }),
          geodesic: true,
          strokeColor: '#555',
          strokeWeight: 2.5,
          map: map
        });
      <?php endif; ?>

      coordinates.map(function(coordinate, index) {
        bounds.extend(new google.maps.LatLng(
          parseFloat(coordinate.lat),
          parseFloat(coordinate.lng)
        ));

        if (coordinates[index + 1]) {
          var polyline = new google.maps.Polyline({
            path: [coordinate, coordinates[index + 1]],
            geodesic: true,
            strokeColor: 'steelblue',
            strokeOpacity: parseFloat((coordinate.speed / 10) + 0.05),
            strokeWeight: 5,
            map: map
          });

          google.maps.event.addListener(polyline, 'mouseover', function(e) {
            infoWindow.setPosition(e.latLng);
            infoWindow.setContent([
              '<h6>Detalles cordenada</h6>',
              '<div>Velocidad: ' + coordinate.speed.toFixed(2) + 'km/h<div>',
              '<div>Hora: ' + new Date(coordinate.timestamp).toLocaleString('es-ES', { timeZone: 'Atlantic/Canary' }) + '</div>'
            ].join(''));
            infoWindow.open(map);
          });

          google.maps.event.addListener(polyline, 'mouseout', function() {
            infoWindow.close();
          });
        }
      });

      map.fitBounds(bounds);
    }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= get_option('race_map_key'); ?>&callback=initMap&libraries=places,geometry"></script>
  </body>
</html>
