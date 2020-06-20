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

    #scene {
        position: absolute;
        width: 80%;
        height: 60%;
    }

    #ui {
        position: absolute;
        background-color: lightgrey;
        bottom:0; 
        left:0;
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
                    <option value="cm">cm</option>
                    <option value="mm">mm</option>
                    <option value="m">m</option>
                    <option value="in">in</option>
                    <option value="ft">ft</option>
                </select>
                <label>Postion: </label>
                <input type="text" disabled="true" id="projectilePos" style="width:40%">
            </div>
            <div>
                <button id="launch">Launch</button>
                <label>Rotate Catapult (in Degrees): </label>
                <input type="number" id="rotateCatapult" step="1" min="-180" max="180" value="0">
                <button id="reset">Reset Catapult</button>
                <select id="launchAngle">
                    <option value="angle1">22.5 Degrees</option>
                    <option value="angle2">45 Degrees</option>
                    <option value="angle3">67.5 Degrees</option>
                    <option value="angle4">90 Degrees</option>
                </select>
                <label>Force (in Newtons): </label>
                <input type="number" id="setForce" step="1" min="1" max="10" value="1">
                <label>Mass (in Grams): </label>
                <input type="number" id="setMass" step="1" min="1" max="10" value="1">
            </div>
            <div>
                <button id="resetCamera">Reset Camera</button>
                <input type="checkbox" id="followTrajectory" name="followTrajectory">
                <label for="followTrajectory">Follow Trajectory</label><br>
                <button id="exportCSV">Export Data</button>
            </div>
        </div>
    </div>
</body>
</html>

<script src="js/libs/ammo.js"></script>
<script type="module" src="js/demos/catapult.js"></script>