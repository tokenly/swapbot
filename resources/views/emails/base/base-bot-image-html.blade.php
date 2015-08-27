@extends('emails.base.base-html')

@section('subheader')
            <table class="container">
              <tr>
                <td>
                  <table class="row">
                    <tr>
                      <td class="wrapper">
                        <table class="ten columns">
                          <tr>
                            <td>
                              @section('subheaderTitle') @show
                            </td>
                            <td class="expander"></td>
                          </tr>
                        </table>
                      </td>
                      <td class="wrapper last">
                        <table class="two columns">
                          <tr>
                            <td>
                              <img src="{{ $robohashUrl }}" alt="RoboHash" width="80" height="80">
                            </td>
                            <td class="expander"></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
@stop
