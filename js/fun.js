// Localization Demo EM

function doTheFunnyCountingInTheConsole() {
    var jmo = ExternalModules.RUB.LocalizationDemoExternalModule
    console.log(jmo.tt('countup'))
    const arr = jmo.tt('array')
    for (var i = 0; i < arr.length; i++) {
        console.log(arr[i])
    }
}