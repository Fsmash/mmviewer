<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>MMViewer Test</title>

</head>
<body>
</body>
</html>

<script src="js/libs/ammo.js"></script>

<script type="module">

    import { THREE, Stats } from "/js/mmviewer/mmviewer.js";

    var camera, scene, renderer, stats;
    var geometry, material, mesh;
 
    init();
    animate();
 
    function init() {
     
        camera = new THREE.PerspectiveCamera( 70, window.innerWidth / window.innerHeight, 0.01, 10 );
        camera.position.z = 1;
     
        scene = new THREE.Scene();
     
        geometry = new THREE.BoxGeometry( 0.2, 0.2, 0.2 );
        material = new THREE.MeshNormalMaterial();
     
        mesh = new THREE.Mesh( geometry, material );
        scene.add( mesh );
     
        renderer = new THREE.WebGLRenderer( { antialias: true } );
        renderer.setSize( window.innerWidth, window.innerHeight );
        document.body.appendChild( renderer.domElement );

        stats = new Stats();
        stats.domElement.style.position = 'absolute';
        stats.domElement.style.top = '0px';
        document.body.appendChild(stats.domElement);
     
    }
     
    function animate() {
     
        requestAnimationFrame( animate );
     
        mesh.rotation.x += 0.01;
        mesh.rotation.y += 0.02;
     
        renderer.render( scene, camera );
        stats.update();
     
    }

</script>