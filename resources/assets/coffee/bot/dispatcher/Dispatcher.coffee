# ---- begin references
invariant = require '../dispatcher/invariant'
# ---- end references

invariant = require './invariant'

exports = {}

_prefix = 'ID_'

_lastID = 1
_callbacks = {}
_isPending = {}
_isHandled = {}
_isDispatching = false
_pendingPayload = null


exports.sayHi = ()->
  # console.log "Dispatcher says hi"

exports.register = (callback) ->
  id = _prefix + _lastID++
  _callbacks[id] = callback
  id

exports.unregister = (id) ->
  invariant _callbacks[id], 'Dispatcher.unregister(...): `%s` does not map to a registered callback.', id
  delete _callbacks[id]
  return

exports.waitFor = (ids) ->
  invariant _isDispatching, 'Dispatcher.waitFor(...): Must be invoked while dispatching.'
  ii = 0
  while ii < ids.length
    id = ids[ii]
    if _isPending[id]
      invariant _isHandled[id], 'Dispatcher.waitFor(...): Circular dependency detected while ' + 'waiting for `%s`.', id
      continue
    invariant _callbacks[id], 'Dispatcher.waitFor(...): `%s` does not map to a registered callback.', id
    _invokeCallback id
    ii++
  return

exports.dispatch = (payload) ->
  invariant !_isDispatching, 'Dispatch.dispatch(...): Cannot dispatch in the middle of a dispatch.'
  _startDispatching payload
  try
    for id of _callbacks
      if _isPending[id]
        continue
      _invokeCallback id
  finally
    _stopDispatching()
  return

exports.isDispatching = ->
  _isDispatching




_invokeCallback = (id) ->
  _isPending[id] = true
  _callbacks[id] _pendingPayload
  _isHandled[id] = true
  return

_startDispatching = (payload) ->
  for id of _callbacks
    _isPending[id] = false
    _isHandled[id] = false
  _pendingPayload = payload
  _isDispatching = true
  return

_stopDispatching = ->
  _pendingPayload = null
  _isDispatching = false
  return

# #############################################
module.exports = exports
