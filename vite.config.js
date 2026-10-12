import { defineConfig } from 'vite';
import { createHash } from 'node:crypto';
import { readdirSync, statSync } from 'node:fs';
import { basename, resolve } from 'node:path';

/**
 * Collect entry points from src/scripts and src/styles.
 * Files starting with a dot or underscore (partials) are skipped.
 *
 * @return {Object<string, string>}
 */
const getEntries = () => {
  const entries = {};

  [ 'scripts', 'styles' ].forEach( ( folder ) => {
    const dir = resolve( import.meta.dirname, 'src', folder );

    readdirSync( dir )
      .filter( ( file ) => statSync( `${ dir }/${ file }` ).isFile() && ! /^[._]/.test( file ) )
      .forEach( ( file ) => {
        entries[ `${ folder }/${ file.replace( /\.[^.]+$/, '' ) }` ] = `${ dir }/${ file }`;
      } );
  } );

  return entries;
};

/**
 * Stylesheet entries produce an empty JS chunk; drop it so only the CSS remains.
 *
 * @return {import('vite').Plugin}
 */
const removeEmptyChunks = () => ( {
  name: 'remove-empty-chunks',
  generateBundle( options, bundle ) {
    Object.entries( bundle ).forEach( ( [ fileName, chunk ] ) => {
      if ( 'chunk' === chunk.type && '' === chunk.code.trim() ) {
        delete bundle[ fileName ];
        delete bundle[ `${ fileName }.map` ];
      }
    } );
  },
} );

/**
 * Write a Laravel Mix style mix-manifest.json: { "/file.js": "/file.js?id=<md5 of the content>" }.
 *
 * @return {import('vite').Plugin}
 */
const mixManifest = () => ( {
  name: 'mix-manifest',
  generateBundle( options, bundle ) {
    const manifest = {};

    Object.values( bundle )
      .filter( ( file ) => ! file.fileName.endsWith( '.map' ) )
      .sort( ( a, b ) => a.fileName.localeCompare( b.fileName ) )
      .forEach( ( file ) => {
        const hash = createHash( 'md5' ).update( 'chunk' === file.type ? file.code : file.source ).digest( 'hex' );
        manifest[ `/${ file.fileName }` ] = `/${ file.fileName }?id=${ hash }`;
      } );

    this.emitFile( {
      type: 'asset',
      fileName: 'mix-manifest.json',
      source: JSON.stringify( manifest, null, 4 ) + '\n',
    } );
  },
} );

export default defineConfig( ( { mode } ) => ( {
  base: './',
  publicDir: false,
  plugins: [ removeEmptyChunks(), mixManifest() ],
  build: {
    outDir: 'public',
    emptyOutDir: true,
    // Like Mix's `.sourceMaps( false )`: source maps in development builds only.
    sourcemap: 'development' === mode,
    cssCodeSplit: true,
    // Keep CSS syntax that older WordPress admin browsers understand (e.g. max-width media queries).
    cssTarget: [ 'chrome100', 'edge100', 'firefox100', 'safari15' ],
    rollupOptions: {
      input: getEntries(),
      // Entries are named scripts/… and styles/… to keep them unique; the files land flat in public/.
      output: {
        entryFileNames: ( { name } ) => `${ basename( name ) }.js`,
        chunkFileNames: '[name].js',
        assetFileNames: ( { names } ) => names?.[ 0 ] ? basename( names[ 0 ] ) : '[name][extname]',
      },
    },
  },
} ) );
