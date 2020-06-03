// TODO: Add type and param checking.
// TODO: Clean up threejs instances properly

import * as THREE from "/js/mmviewer/threejs/three.module.js";
import { OrbitControls } from "/js/mmviewer/threejs/OrbitControls.js";
import { GLTFLoader } from "/js/mmviewer/threejs/GLTFLoader.js"
import Stats from '/js/mmviewer/threejs/stats.module.js';

class MMViewer {

    // Useful debug objects
    stats = null;
    // Rendering Objects
    clock = null;
    camera = null;
    scene = null; 
    renderer = null;
    // Controls
    orbitControls = null;
    //Scene objects
    meshArray = [];          // made up of three.js mesh objects with geometry and material
    groupArray = [];
    plane = null;            // Plane helper
    // Animation
    mixers = [];
    // Light objects
    hemiLight = null;        // Hemisphere Light
    hemiLightHelper = null;
    dirLight = null;
    dirLightHelper = null;
    // lightArray = [];      // made up of three.js light objects that illuminate the scene
    // helperArray = [];     // made up of three.js helper objects for mesh or light objects
    // Loaders
    gltfLoader = null;
    
    constructor(o = {}) {
        
        let fov = o.type === undefined ? 30 : o.type;
        let aspectRatio = o.aspectRatio === undefined ? window.innerWidth/window.innerHeight : o.aspectRatio;
        let near = o.near === undefined ? 1 : o.near;
        let far = o.far === undefined ? 1000 : o.far;
        let width = o.width === undefined ? window.innerWidth : o.width;
        let height = o.height === undefined ? window.innerHeight : o.height;
        let cameraLookAt = o.cameraLookAt === undefined ? [0, 0, 0] : o.cameraLookAt;
        let cameraPos = o.cameraPos === undefined ? [0, 0, 0] : o.cameraPos;
        let shadowMap = o.shadowMap === undefined ? false : o.shadowMap;
        let backgroundColor = o.backgroundColor === undefined ? 0xff0505 : o.backgroundColor;

        // Camera setup
        this.camera = new THREE.PerspectiveCamera(fov, aspectRatio, near, far); // params are fov, aspect ratio, near and far clipping plane
        this.camera.lookAt(cameraLookAt[0], cameraLookAt[1], cameraLookAt[2]);
        this.camera.position.x = cameraPos[0]; // slightly up.
        this.camera.position.y = cameraPos[1]; // slightly up.
        this.camera.position.z = cameraPos[2]; // slightly back.

        // Create scene (GL context)
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(backgroundColor);

        // Create renderer, canvas dom object to render into
        this.renderer = new THREE.WebGLRenderer( { antialias: true } );
        this.renderer.setPixelRatio(width / height);
        this.renderer.setSize(width, height);
        this.renderer.shadowMap.enabled = shadowMap;
        document.body.appendChild(this.renderer.domElement);
        
    }

    initStats() {

        if (this.stats === null) {
            this.stats = new Stats();
            this.stats.domElement.style.position = 'absolute';
            this.stats.domElement.style.top = '0px';
            document.body.appendChild(this.stats.domElement);
        }

    }

    initClock() {

        if (this.clock === null)
            this.clock = new THREE.Clock();

    }

    getDeltaTime() {

        if (this.clock !== null)
            return this.clock.getDelta();

    }


    enableShadowMap() {

        this.renderer.shadowMap.enabled = true;
    
    }

    disableShadowMap() {

        this.renderer.shadowMap.enabled = false;
    
    }

    // Camera Orbit controls setup
    initOrbitControls(min, max) {

        this.orbitControls = new OrbitControls(this.camera, this.renderer.domElement);
        this.orbitControls.minDistance = min;
        this.orbitControls.maxDistance = max;
        //this.orbitControls.update();
        //this.orbitControls.addEventListener('change', this.render.bind(this));

    }

    setOrbitControlsTarget(x, y, z) {

        if (this.orbitControls !== null)
            this.orbitControls.target = new THREE.Vector3(x, y, z);

    }

    updateOrbitControls() {

        if (this.orbitControls !== null)
            this.orbitControls.update();

    }

    // params x, y, z expect decimal values
    setCameraLookAt(x, y, z) {

        this.camera.lookAt(x, y, z);

    }

    // params x, y, z expect decimal values
    setCameraPos(x, y, z) {

        this.camera.position.set(x, y, z);

    }

    // param color, expected Hex value
    setBackgroundColor(color) {
        
        this.scene.background = new THREE.Color(color);

    }

    // Objects in scene to be pushed down to pipline
    // TODO: add ability to add as many light objects into scene
    addLightObject(o) {

    }

    // params lightColor and groundColor expected hex value. intensity 0 to 1 int.
    addHemiLight(lightColor, groundColor, intensity = 1) {
        
        if (this.hemiLight !== null)
            removeHemiLight();
        this.hemiLight = new THREE.HemisphereLight(lightColor, groundColor, intensity);
        this.scene.add(this.hemiLight);

    }

    addHemiLightHelper(color) {
        
        if (this.hemiLight !== null) {
            if (this.hemiLightHelper !== null)
                removeHemiLightHelper();
            this.hemiLightHelper = new THREE.HemisphereLightHelper(this.hemiLight, color);
            this.scene.add(this.hemiLightHelper);
        }

    }

    removeHemiLightHelper() {

        if (this.hemiLightHelper !== null) {
            this.scene.remove(this.hemiLightHelper);
            this.hemiLightHelper = null;
        }

    }

    setHemiLightHSL(hue, saturation, lightness) {

        if (this.hemiLight !== null)
            this.hemiLight.color.setHSL(hue, saturation, lightness);

    }

    setHemiLightGroundHSL(hue, saturation, lightness) {

        if (this.hemiLight !== null)
            this.hemiLight.groundColor.setHSL(hue, saturation, lightness);

    }

    setHemiLightPosition(x, y, z) {

        if (this.hemiLight !== null)
            this.hemiLight.position.set( x, y, z);

    }

    removeHemiLight() {

        if (this.hemiLight !== null) {
            this.scene.remove(this.hemiLight);
            this.hemiLight = null;
        }

    }

    addDirectionalLight(color, intensity) {

        if (this.dirLight !== null)
            removeHemiLight();
        this.dirLight = new THREE.DirectionalLight( 0xffffff , 1);
        this.scene.add(this.dirLight);

    }

    addDirectionalLightHelper(color) {
        
        if (this.dirLight !== null) {
            if (this.dirLightHelper !== null)
                removeDirectionalLightHelper();
            this.dirLightHelper = new THREE.DirectionalLightHelper(this.dirLight, color);
            this.scene.add(this.dirLightHelper);
        }

    }

    removeDirectionalLightHelper() {

        if (this.dirLightHelper !== null) {
            this.scene.remove(this.dirLightHelper);
            this.dirLightHelper = null;
        }

    }    

    setDirectionalLightHSL(hue, saturation, color) {

        if (this.dirLight !== null)
            this.dirLight.color.setHSL(hue, saturation, color);

    }

    setDirectionalLightPosition(x, y, z) {
        
        if (this.dirLight !== null)
            this.dirLight.position.set(x, y, z);

    }

    setDirectionalLightScalar(c) {
        
        if (this.dirLight !== null)
            this.dirLight.position.multiplyScalar(c);

    }

    removeDirectionalLight() {

        if (this.dirLight !== null) {
            this.scene.remove(this.dirLight);
            this.dirLight = null;
        }

    }

    setDirectionalLightShadowMap(width, height) {

        if (this.dirLight !== null) {
            this.dirLight.castShadow = true;
            this.dirLight.shadow.mapSize.width = width;
            this.dirLight.shadow.mapSize.height = height;
        }

    }

    setDirectionalLightShadowOff() {

        if (this.dirLight !== null)
            this.dirLight.castShadow = false;

    }


    setDirectionalLightFrustrum(left, right, top, bottom, far) {
        
        if (this.dirLight !== null) {
            this.dirLight.shadow.camera.left = left;
            this.dirLight.shadow.camera.right = right;
            this.dirLight.shadow.camera.top = top;
            this.dirLight.shadow.camera.bottom = bottom;
            this.dirLight.shadow.camera.far = far;
        }

    }

    // params width, height, depth expect decimal value
    // returns index (id) of pushed back mesh object. inspired by openGL state system
    addNormalBox(width, height, depth) {

        let geometry = new THREE.BoxGeometry(width, height, depth);
        let material = new THREE.MeshNormalMaterial();
        var index = this.meshArray.push(new THREE.Mesh(geometry, material)) - 1;
        
        this.scene.add(this.meshArray[index]);
        
        return index;

    }

    // params width, height, depth expect decimal value
    // returns index (id) of pushed back mesh object. inspired by openGL state system
    addBox(width, height, depth, boxColor = 0xa0afa4) {

        let geometry = new THREE.BoxGeometry(width, height, depth);
        let material = new THREE.MeshPhongMaterial({color: boxColor});
        var index = this.meshArray.push(new THREE.Mesh(geometry, material)) - 1;
        
        this.scene.add(this.meshArray[index]);
        
        return index;

    }

    addSphere(radius, sphereColor = 0xff0505) {

        let geometry = new THREE.SphereBufferGeometry(radius);
        let material = new THREE.MeshPhongMaterial({color: sphereColor});
        var index = this.meshArray.push(new THREE.Mesh(geometry, material)) - 1;
        
        this.scene.add(this.meshArray[index]);
        
        return index;

    }

    setObjectPos(index, x, y, z) {
        
        if (index >= this.meshArray.length)
            alert(index + " is out of bounds of meshArray");
        else
            this.meshArray[index].position.set(x, y, z);

    }

    setObjectScale(index, x, y, z) {

        if (index >= this.meshArray.length)
            alert(index + " is out of bounds of meshArray");
        else
            this.meshArray[index].scale.set(x, y, z);

    }

    setObjectCastShadow(index, bool) {

        if (index >= this.meshArray.length)
            alert(index + " is out of bounds of meshArray");
        else
            this.meshArray[index].castShadow = bool;

    }

    setObjectReceiveShadow(index, bool) {

        if (index >= this.meshArray.length)
            alert(index + " is out of bounds of meshArray");
        else
            this.meshArray[index].receiveShadow = bool;

    }

    getObject(index) {
        
        if (index >= this.meshArray.length)
            alert(index + " is out of bounds of meshArray");
        else
            return this.meshArray[index];

    }

    // TODO: add more primitive shapes and material types
    addObject(o) {

        o = o || {};

        let primitiveType = o.type === undefined ? 'box' : o.type;
        let materialType = o.material === undefined ? 'phong' : o.material;
        let position = o.position === undefined ? [0, 0, 0] : o.position;
        let scale = o.scale === undefined ? [1, 1, 1] : o.scale;
        let castShadow = o.castShadow === undefined ? false : o.castShadow;
        let receiveShadow = o.receiveShadow === undefined ? false : o.receiveShadow;
        let materialColor = o.color === undefined ? 0xff0505 : o.color;

        let geometry;
        let material;
        
        // Primitive type 
        if (primitiveType === 'box') {
            let size = o.size === undefined ? [1, 1, 1] : o.size;
            geometry = new THREE.BoxGeometry(size[0], size[1], size[2]);
        }
        else if (primitiveType === 'sphere') {
            let radius = o.radius === undefined ? 1 : o.radius;
            geometry = new THREE.SphereBufferGeometry(radius);
        }

        // Material
        if (materialType === 'phong') {
            material = new THREE.MeshPhongMaterial({color: materialColor});
        }
        else if (materialType === 'normal') {
            material = new THREE.MeshNormalMaterial();
        }

        // Create and add mesh object to scene
        var index = this.meshArray.push(new THREE.Mesh(geometry, material)) - 1;
        // Set position
        this.meshArray[index].position.set(position[0], position[1], position[2]);
        // Set scale    
        this.meshArray[index].scale.set(scale[0], scale[1], scale[2]);
        // Set cast shadow
        this.meshArray[index].castShadow = castShadow;
        // Set receive shadow
        this.meshArray[index].receiveShadow = receiveShadow;

        this.scene.add(this.meshArray[index]);
        
        return index;

    }

    removeObject(index) {
        
        if (index >= this.meshArray.length)
            alert(index + " is out of bounds of meshArray");
        else {
            this.scene.remove(this.meshArray[index]);
            this.meshArray[index].geometry.dispose();
            this.meshArray[index].material.dispose();
            this.meshArray.splice(index, 1);
        }

    }

    addPlane(size, divisions) {


        if (this.plane !== null)
            removePlane();
        this.plane = new THREE.GridHelper(size, divisions);
        this.scene.add(this.plane);

    }

    removePlane() {

        this.scene.remove(this.plane);
        this.plane = null;

    }

    animate() {
       
        window.requestAnimationFrame(this.animate.bind(this));
        this.renderer.render(this.scene, this.camera);
        if (this.stats !== null)
            this.stats.update();
        
    }

    animateWithCallBack(callBack) {
       
        window.requestAnimationFrame(this.animateWithCallBack.bind(this, callBack));
        callBack();
        this.renderer.render(this.scene, this.camera);
        if (this.stats !== null)
            this.stats.update();
        
    }

    render() {
                
        this.renderer.render(this.scene, this.camera);
            
    }

    loadGLTF(path, o = {}) {
        
        if (this.gltfLoader === null)
            this.gltfLoader = new GLTFLoader();

        let position = o.position === undefined ? [0, 0, 0] : o.position;
        let scale = o.scale === undefined ? [1, 1, 1] : o.scale;
        let castShadow = o.castShadow === undefined ? false : o.castShadow;
        let receiveShadow = o.receiveShadow === undefined ? false : o.receiveShadow;
        let materialType = o.material === undefined ? 'none' : o.material;
        let scene = this.scene;
        let groupArray = this.groupArray;
        let material = null;

        // Material
        if (materialType !== 'none') {

            
            if (materialType === 'phong') {
                let materialColor = o.color === undefined ? 0xff0505 : o.color;
                material = new THREE.MeshPhongMaterial({color: materialColor});
            }
            else if (materialType === 'normal') {
                material = new THREE.MeshNormalMaterial();
            }

        }

        this.gltfLoader.load(path, function(gltf) {

            if (material !== null) {

                let children = gltf.scene.children;
                for (var i = children.length - 1; i >= 0; i--) {
                    if (children[i].isMesh) {
                        children[i].material.dispose();
                        children[i].material = material;
                    }
                }

            }

            gltf.scene.position.set(position[0], position[1], position[2]);
            gltf.scene.scale.set(scale[0], scale[1], scale[2]);
            gltf.scene.castShadow = castShadow;
            gltf.scene.receiveShadow = receiveShadow;
            scene.add(gltf.scene);
            groupArray.push(gltf.scene);

        });
    
    }

    async loadAsyncGLTF(path, o = {}) {
        
        if (this.gltfLoader === null)
            this.gltfLoader = new GLTFLoader();

        let promise = await this.gltfLoader.loadAsync(path);
        return promise;
    
    }

    setModelMesh(index, o = {}) {

        if (index >= this.meshArray.length)
            alert(index + " is out of bounds of meshArray");
        else {

            let materialType = o.material === undefined ? 'phong' : o.material;
            let children = this.groupArray[index].children;
            let material;

            if (materialType === 'phong') {
                let materialColor = o.color === undefined ? 0xff0505 : o.color;
                material = new THREE.MeshPhongMaterial({color: materialColor});
            }
            else if (materialType === 'normal') {
                material = new THREE.MeshNormalMaterial();
            }

            for (var i = children.length - 1; i >= 0; i--) {

                if (children[i].isMesh) {        
                    
                    children[i].material.dispose();                    
                    children[i].material = material;

                }
            
            }

        }

    }

}

export { MMViewer, THREE, Stats };