<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MMViewer Test</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <style>
        
        div {
            margin: 5px;
        }

        #ui {
            position: absolute;
            background-color: lightgrey;
            bottom:0; 
            right:0;
            border-radius: 10px;
            width: 40%;
            padding: 10px;
        }

        #setForce {
            width: 8%;
        }

    </style>

</head>
<body>
    <div id="scene">
        <div id="ui">
            <div>
                <label>Distance: </label>
                <input type="text" disabled="true" id="projectileDist" style="width:20%">
                <select id="units">
                  <option value="mm">mm</option>
                  <option value="cm">cm</option>
                  <option value="m">m</option>
                  <option value="in">in</option>
                  <option value="ft">ft</option>
                </select>
                <label>Postion: </label>
                <input type="text" disabled="true" id="projectilePos" style="width:40%">
            </div>
            <div>
                <button id="launch">Launch</button>
                <button id="reset">Reset Catapult</button>
            </div>
            <div>
                <select id="launchAngle">
                  <option value="angle1">22.5 Degrees</option>
                  <option value="angle2">45 Degrees</option>
                  <option value="angle3">67.5 Degrees</option>
                  <option value="angle4">90 Degrees</option>
                </select>
                <label>Force (in Newtons): </label>
                <input type="number" id="setForce" step="1" min="1" max="10" value="1">
            </div>
            <div>
                <button id="resetCamera">Reset Camera</button>
                <input type="checkbox" id="followTrajectory" name="followTrajectory">
                <label for="followTrajectory">Follow Trajectory</label><br>
            </div>
        </div>
    </div>
</body>
</html>

<script src="js/libs/ammo.js"></script>
<script type="module">

    import { MMViewer, THREE } from "/js/mmviewer/mmviewer.js";

    // Physics globals
    let physicsWorld = null, tmpTrans = null;
    let tmpPos = new THREE.Vector3(), tmpQuat = new THREE.Quaternion();
    
    // Graphics globals
    let viewer = null;                  // three.js viewer global
    // mesh ojbects
    let catapultArm = null, projectile = null, bar1 = null, bar2 = null, bar3 = null, bar4 = null, trajectoryLine = null;
    let drawCount = 0;
    let launch = false;
    let readyToAnimate = false;
    let setUpToLaunch = false;
    let originalProjectilePos = null;
    let followTrajectory = false;
    // let gotDistance = false;

    // Angles (in radians)
    let armAngle = 0;
    let maxAngle = 0;
    let originalArmAngle = 0;
    const angle1 = 0.3926991;   // 22.5
    const angle2 = 0.785398;    // 45
    const angle3 = 1.178097;    // 67.5
    const angle4 = 1.5708;      // 90
    const maxPoints = 9000;
    let launchAngle = angle1;
    let force = 200;
    let unitConversion = 1;

    Ammo().then(start);
    
    function start() {

        // Initialize global world transform
        tmpTrans = new Ammo.btTransform();
        
        // Set up physics and graphics
        initPhysics();
        initViewer();
        
        // Add Plane and Ball into viewer (scene)
        createPlane();
        createCatapult();
        createTrajectoryLine();
        setUpCallBacks();

        // Animate scene with call back to update physics with delta time
        viewer.animateWithCallBack(function() {

            if (readyToAnimate && launch) {

                if (armAngle <= maxAngle) {

                    catapultArm.rotation.x += 0.1;
                    armAngle = catapultArm.rotation.x;
                    rotateAboutPoint(projectile, catapultArm.position, new THREE.Vector3(1, 0, 0), 0.1);
                    setUpToLaunch = true;

                }
                else if(setUpToLaunch) {

                    createDynamicProjectile();
                    let launchVector = new THREE.Vector3(0, 1, 0);
                    launchVector.applyAxisAngle(new THREE.Vector3(1, 0, 0), launchAngle);
                    projectile.userData.physicsBody.setLinearVelocity(new Ammo.btVector3(0, launchVector.y * force, launchVector.z * force));
                    // gotDistance = 
                    setUpToLaunch = false;
                    viewer.initClock();
                    
                    if (followTrajectory)
                        viewer.camera.position.x = -1000;

                }
                else {

                    let deltaTime = viewer.getDeltaTime();
                    updatePhysics(deltaTime);
                    
                    if ((drawCount) <= (maxPoints))
                        updateTrajectoryLine();

                    if (followTrajectory) {
                        
                        viewer.camera.position.z = projectile.position.z;
                        viewer.setOrbitControlsTarget(projectile.position.x, projectile.position.y, projectile.position.z);
                        viewer.updateOrbitControls();

                    }

                    let projectilePos = projectile.position;
                    
                    $('#projectilePos').val('x: ' + (projectilePos.x / unitConversion).toFixed(2) + ' y: ' + (projectilePos.y / unitConversion).toFixed(2) + ' z: ' + (projectilePos.z / unitConversion).toFixed(2));
                        
                    let catapultPos = viewer.groupArray[0].position;
                    let dist = catapultPos.distanceTo(projectilePos);
                    
                    $('#projectileDist').val((dist / unitConversion).toFixed(2) + ' ' +  $('#units').val());

                    // if (projectilePos.y <= 15 && !gotDistance) {
                        
                    //     let catapultPos = viewer.groupArray[0].position;
                    //     let dist = catapultPos.distanceTo(projectilePos);
                        
                    //     $('#projectileDist').val((dist / unitConversion).toFixed(2) + ' cm');
                    //     gotDistance = true;

                    // }

                }

            }

        });

    }

    function setUpCallBacks() {
        
        $('#units').change(function() {

            let unit = this.value;

            if (unit === 'mm')
                unitConversion = 1;
            else if (unit === 'cm') 
                unitConversion = 10;
            else if (unit === 'm')
                unitConversion = 100;
            else if (unit === 'in')
                unitConversion = 25.4;
            else if (unit === 'ft')
                unitConversion = 305;

        })

        $('#reset').click(function() {

            $('#projectilePos').val('');
            $('#projectileDist').val('');
            launch = false;
            drawCount = 0;

            if (trajectoryLine !== null)
                trajectoryLine.geometry.setDrawRange(0, drawCount);
        
            if (readyToAnimate) {

                catapultArm.rotation.x = originalArmAngle;
                armAngle = catapultArm.rotation.x;
                maxAngle = armAngle + launchAngle;
                projectile.position.set(originalProjectilePos.x, originalProjectilePos.y, originalProjectilePos.z);

            }

        });

        $('#launch').click(function() {

            launch = true;

        });

        $('#setForce').change(function () {

            force = (this.value * 100) + 100;

        })

        $('#launchAngle').change(function() {

            bar1.visible = false;
            bar2.visible = false;
            bar3.visible = false;
            bar4.visible = false;
        
            if (this.value === 'angle1') {
                launchAngle = angle1;
                bar1.visible = true;
            }
            else if (this.value === 'angle2') {
                launchAngle = angle2;
                bar2.visible = true;
            }
            else if (this.value === 'angle3') {
                launchAngle = angle3;
                bar3.visible = true;
            }
            else {
                launchAngle = angle4;
                bar4.visible = true;
            }

            $('#reset').trigger('click');

        });

        $('#resetCamera').click(function () {

            viewer.setOrbitControlsTarget(0, 0, 0);
            viewer.setCameraPos(-1018, 503, -1.2);
            viewer.updateOrbitControls();
            
        })

        $('#followTrajectory').change(function() {
            
            followTrajectory = this.checked;

            if (!followTrajectory)
                $('#resetCamera').trigger('click');

        });

    }

    // Rotates object around pivot (point) about the specified axis 
    function rotateAboutPoint(obj, point, axis, theta) {

        obj.position.sub(point); // remove the offset
        obj.position.applyAxisAngle(axis, theta); // rotate the POSITION
        obj.position.add(point); // re-add the offset
        obj.rotateOnAxis(axis, theta); // rotate the OBJECT

    }

    // Initialize physics engine
    function initPhysics() {

        let collisionConfiguration = new Ammo.btDefaultCollisionConfiguration(),
            dispatcher             = new Ammo.btCollisionDispatcher(collisionConfiguration),
            overlappingPairCache   = new Ammo.btDbvtBroadphase(),
            solver                 = new Ammo.btSequentialImpulseConstraintSolver();

        physicsWorld = new Ammo.btDiscreteDynamicsWorld(dispatcher, overlappingPairCache, solver, collisionConfiguration);
        physicsWorld.setGravity(new Ammo.btVector3(0, -105, 0));

    }

    // Initialize three js canvas viewer
    function initViewer() {
    
        // Window width and height
        let width = window.innerWidth;
        let height = window.innerHeight;

        // MMViewer initialization
        viewer = new MMViewer({far: 50000});
        
        viewer.initStats();
        viewer.setBackgroundColor(0x87ceeb);

        // Enable shadow map
        viewer.enableShadowMap();
        
        // Adding hemisphere light to scene
        viewer.addHemiLight(0xffffff, 0xffffff, 0.1);
        viewer.setHemiLightHSL(0.6, 0.6, 0.6);
        viewer.setHemiLightGroundHSL(0.1, 1, 0.4);
        viewer.setHemiLightPosition(0, 500, 100);
        
        // Adding directional light to scene
        viewer.addDirectionalLight(0xffffff, 1);
        viewer.setDirectionalLightHSL(0.1, 1, 0.95);
        viewer.setDirectionalLightPosition(-1, 1.75, 1);
        viewer.setDirectionalLightScalar(500);
        viewer.setDirectionalLightShadowMap(2048, 2048);
        let d = 1000;
        viewer.setDirectionalLightFrustrum(-d, d, d, -d, 13500);
        
        // Initialize orbit controls
        viewer.initOrbitControls(500, 15000);
        viewer.setOrbitControlsTarget(0, 0, 0);
        viewer.setCameraPos(-1018, 503, -1.2);
        viewer.updateOrbitControls();
    
    }

    // Object creation
    function createPlane() {
    
        let pos = {x: 0, y: 0, z: 7200};
        let scale = {x: 500, y: 2, z: 15000};
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass = 0;
        let planeIndex;

        // threeJS Section
        if (viewer !== null) {

            planeIndex = viewer.addObject({ type: 'box', position: [pos.x, pos.y, pos.z], scale: [scale.x, scale.y, scale.z], color: 0xbfd1e5 });

        }

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos.x, pos.y, pos.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let colShape = new Ammo.btBoxShape(new Ammo.btVector3(scale.x * 0.5, scale.y * 0.5, scale.z * 0.5));
        colShape.setMargin(0.05);

        let localInertia = new Ammo.btVector3(0, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(0.8);
        body.setFriction(4);
        body.setRollingFriction(10);
        physicsWorld.addRigidBody(body);

        return planeIndex;

    }

    function createCatapult() {

        let gltfPromise = viewer.loadAsyncGLTF('/models/catapult.glb');
        
        gltfPromise.then(function(gltf) {

            let material = new THREE.MeshPhongMaterial({ color: 0xff0505 });
            let children = gltf.scene.children;
            
            for (var i = children.length - 1; i >= 0; i--) {
                
                if (children[i].isMesh) {

                    let name = children[i].name;
                    children[i].material.dispose();
                    
                    if (name === 'barWRidges002') {
                        
                        bar1 = children[i];
                        children[i].material = material;

                    }
                    else if (name === 'barWRidges003') {
                        
                        bar2 = children[i];
                        children[i].visible = false;
                        children[i].material = material;

                    }
                    else if (name === 'barWRidges004') {
                        
                        bar3 = children[i];
                        children[i].visible = false;
                        children[i].material = material;

                    }
                    else if (name === 'barWRidges005') {
                        
                        bar4 = children[i];
                        children[i].visible = false;
                        children[i].material = material;

                    }
                    else if (name === 'projectile') {
                        
                        projectile = children[i];
                        children[i].material = new THREE.MeshPhongMaterial({ color: 0xffd700 });

                    }
                    else if (name === 'catArmXL001') {

                        catapultArm = children[i];
                        children[i].material = material;

                    }
                    else {

                        children[i].material = material;

                    }

                }

            }

            originalProjectilePos = new THREE.Vector3(projectile.position.x, projectile.position.y, projectile.position.z);
            originalArmAngle = armAngle = catapultArm.rotation.x;
            maxAngle = armAngle + launchAngle;

            viewer.groupArray.push(gltf.scene);
            viewer.scene.add(gltf.scene);
            readyToAnimate = true;

        });

    }

    function createDynamicProjectile() {

        if (projectile.userData.physicsBody) {

            physicsWorld.removeRigidBody(projectile.userData.physicsBody);
            Ammo.destroy(projectile.userData.physicsBody);
            projectile.userData.physicsBody = null;

        }
        
        let mass = 1;
        let radius = 7.5;

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(projectile.position.x, projectile.position.y, projectile.position.z));
        transform.setRotation(new Ammo.btQuaternion(projectile.quaternion.x, projectile.quaternion.y, projectile.quaternion.z, projectile.quaternion.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let colShape = new Ammo.btSphereShape(radius);
        colShape.setMargin(0.05);

        let localInertia = new Ammo.btVector3(0, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(0.8);
        body.setFriction(4);
        body.setRollingFriction(10);
        physicsWorld.addRigidBody(body);

        // Store ammo js physics prototype
        projectile.userData.physicsBody = body;

    }

    function createTrajectoryLine() {
        
        let material = new THREE.LineBasicMaterial({ color: 0x0000ff, depthTest: false });
        let geometry = new THREE.BufferGeometry();
        let positions = new Float32Array(maxPoints); // 3 vertices per point

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        trajectoryLine = new THREE.Line(geometry, material);
        viewer.meshArray.push(trajectoryLine);
        viewer.scene.add(trajectoryLine);

    }

    function updateTrajectoryLine() {

        var positions = trajectoryLine.geometry.attributes.position.array;
        
        positions[drawCount * 3] = projectile.position.x;
        positions[(drawCount * 3) + 1] = projectile.position.y;
        positions[(drawCount * 3) + 2] = projectile.position.z;
        drawCount += 1;
        
        trajectoryLine.geometry.setDrawRange(0, drawCount);
        trajectoryLine.geometry.attributes.position.needsUpdate = true; // required after the first render
        trajectoryLine.geometry.computeBoundingSphere();

    }

    function updatePhysics(deltaTime) {

        if (deltaTime === 0)
            return;
        
        physicsWorld.stepSimulation(deltaTime, 10, 1 / 800);
        let objAmmo = projectile.userData.physicsBody;
        let ms = objAmmo.getMotionState();

        if (ms) {
                
            ms.getWorldTransform(tmpTrans);
            let p = tmpTrans.getOrigin();
            let q = tmpTrans.getRotation();
            projectile.position.set(p.x(), p.y(), p.z());
            projectile.quaternion.set(q.x(), q.y(), q.z(), q.w());
                
        }

    }

</script>