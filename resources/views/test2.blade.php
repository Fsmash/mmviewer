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

    import { MMViewer } from "/js/mmviewer/mmviewer.js";

    let viewer = null;
    let box = null;
 
    init();
    animate();
 
    function init() {
        
        let o = {fov: 70, near: 0.01, far: 10};
        viewer = new MMViewer(o);
        viewer.initStats();
        viewer.setCameraPos(0, 0, 1);
        let index = viewer.addNormalBox(0.2, 0.2, 0.2);
        box = viewer.getObject(index);
     
    }


     
    function animate() {

        // Animate scene with call back to update physics with delta time
        viewer.animateWithCallBack(function() {

            box.rotation.x += 0.01;
            box.rotation.y += 0.02;

        });
     
    }

</script>