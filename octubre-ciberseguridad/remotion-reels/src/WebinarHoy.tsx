import React, { useEffect, useState } from "react";
import { AbsoluteFill, continueRender, delayRender, interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { TransitionSeries, linearTiming } from "@remotion/transitions";
import { slide } from "@remotion/transitions/slide";
import { fade } from "@remotion/transitions/fade";
import { Backdrop, CYAN, Chip, FAMILY, Flags, Mascota, NAVY, Overlay, Particles, SKY, clampOpts, ease, fontsReady } from "./Reel";
import { Words, type WebinarProps } from "./Webinar";

export type HoyProps = WebinarProps & { readonly frase: string; readonly dy: number };

const Label: React.FC<{ text: string; top: number }> = ({ text, top }) => {
  const frame = useCurrentFrame();
  return (
    <div style={{ position: "absolute", left: 80, top, fontFamily: FAMILY, fontSize: 28, fontWeight: 700, letterSpacing: 8, color: CYAN, textTransform: "uppercase", opacity: interpolate(frame, [4, 20], [0, 1], clampOpts), translate: `${interpolate(frame, [4, 24], [-50, 0], { ...clampOpts, easing: ease })}px 0px` }}>
      {text}
    </div>
  );
};

const Hoy: React.FC<HoyProps> = ({ frase, fondo, seed, dy }) => (
  <AbsoluteFill>
    <Backdrop fondo={fondo} dim={0.5} zoomFrom={1} />
    <Particles seed={seed} />
    <AbsoluteFill style={{ translate: `0px ${dy}px` }}>
    <Label text="Webinar · Mes de la Ciberseguridad" top={330} />
    <Words text={frase} size={96} delay={10} top={410} hlFrom={frase.split(" ").length - 1} />
    <Mascota left={610} top={845} height={390} delay={46} from="bottom" />
    </AbsoluteFill>
  </AbsoluteFill>
);

const Titulo: React.FC<HoyProps> = ({ gancho, marca, fondo, seed, dy }) => {
  const frame = useCurrentFrame();
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.62} zoomFrom={1.1} />
      <Particles seed={seed + 4} />
    <AbsoluteFill style={{ translate: `0px ${dy}px` }}>
      <Words text={gancho[0]} size={112} delay={4} top={330} />
      <Words text={gancho[1]} size={72} color={CYAN} delay={26} top={650} />
      <div style={{ position: "absolute", left: 80, top: 1000, opacity: interpolate(frame, [40, 56], [0, 1], clampOpts), translate: `0px ${interpolate(frame, [40, 62], [40, 0], { ...clampOpts, easing: ease })}px` }}>
        <Chip marca={marca} height={92} />
      </div>
    </AbsoluteFill>
    </AbsoluteFill>
  );
};

const Cierre: React.FC<HoyProps> = ({ marca, fondo, horaChile, horaPeruColombia, dia, mes, seed, dy }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const pop = (d: number) => spring({ frame: frame - d, fps, config: { damping: 12 } });
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.58} zoomFrom={1.04} />
      <Particles seed={seed + 9} />
    <AbsoluteFill style={{ translate: `0px ${dy}px` }}>
      <Label text={`${dia} de ${mes} · Webinar`} top={330} />
      <Words text={horaChile} size={104} delay={8} top={400} />
      <Words text={horaPeruColombia} size={54} color={SKY} delay={22} top={570} weight={700} />
      <div style={{ position: "absolute", left: 80, width: 500, top: 730, height: 112, borderRadius: 56, background: "linear-gradient(135deg,#2a5aa8,#3f86d0)", display: "flex", alignItems: "center", justifyContent: "center", fontFamily: FAMILY, fontSize: 50, fontWeight: 800, color: "#fff", boxShadow: `0 0 ${36 + Math.sin(frame / 5) * 22}px rgba(111,227,245,.7)`, scale: pop(30) * (1 + Math.sin(frame / 6) * 0.015), transformOrigin: "left center" }}>
        Regístrate
      </div>
      <div style={{ position: "absolute", left: 80, top: 880, opacity: interpolate(frame, [40, 54], [0, 1], clampOpts) }}><Chip marca={marca} height={74} /></div>
      <div style={{ position: "absolute", left: 80, top: 1010, display: "flex" }}><Flags size={62} delay={48} /></div>
      <div style={{ position: "absolute", left: 80, top: 1110, fontFamily: FAMILY, fontSize: 36, fontWeight: 700, color: "#fff", opacity: interpolate(frame, [56, 70], [0, 1], clampOpts) }}>#webinarscpnnet</div>
      <Mascota left={600} top={690} height={540} delay={36} />
    </AbsoluteFill>
    </AbsoluteFill>
  );
};

export const WebinarHoy: React.FC<HoyProps> = (props) => {
  const [handle] = useState(() => delayRender("Cargando fuentes"));
  useEffect(() => {
    fontsReady.then(() => continueRender(handle));
  }, [handle]);
  const { durationInFrames } = useVideoConfig();
  const T = 15;
  return (
    <AbsoluteFill style={{ backgroundColor: NAVY, fontFamily: FAMILY }}>
      <TransitionSeries>
        <TransitionSeries.Sequence name="Hoy" durationInFrames={135}><Hoy {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Título" durationInFrames={135}><Titulo {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-right" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Cierre" durationInFrames={135}><Cierre {...props} /></TransitionSeries.Sequence>
      </TransitionSeries>
      <Overlay total={durationInFrames} />
    </AbsoluteFill>
  );
};
