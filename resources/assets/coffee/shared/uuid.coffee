# botUtils functions


exports = {}

# #############################################
# local

s4 = ()->
    return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1)

# #############################################
# exports

exports.uuid4 = ()->
    return s4()+s4()+'-'+s4()+'-'+s4()+'-'+s4()+'-'+s4()+s4()+s4()


module.exports = exports

