import React, { useEffect, useState } from "react";
import { AbsoluteFill, continueRender, delayRender, interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { TransitionSeries, linearTiming } from "@remotion/transitions";
import { slide } from "@remotion/transitions/slide";
import { fade } from "@remotion/transitions/fade";
import { CrossedOff, Highlight } from "@remotion/rough-notation";
import { Backdrop, Chip, FAMILY, Flags, Mascota, NAVY, Overlay, Particles, clampOpts, ease, fontsReady, type Brand } from "./Reel";
import { Words } from "./Webinar";

export type DomumProps = {
  readonly brand: Brand;
  readonly marca: string;
  readonly fondo: string;
  readonly etiqueta: string;
  readonly gancho: string;
  readonly p1: string;
  readonly p2: string;
  readonly p3: string;
  readonly cierre: string;
  readonly seed: number;
};

const HI = "var(--hi, #6fe3f5)";
const ACC = "var(--acc, #3A84BD)";
const ONACC = "var(--onacc, #fff)";
const glass: React.CSSProperties = { background: "rgba(0,0,0,.34)", border: "2px solid rgba(255,255,255,.14)", borderRadius: 40, boxShadow: "0 24px 60px rgba(0,0,0,.4)" };

const Badge: React.FC<{ n: number }> = ({ n }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const p = spring({ frame, fps, config: { damping: 11, stiffness: 140 } });
  return (
    <div style={{ position: "absolute", left: 80, top: 290, width: 92, height: 92, borderRadius: "50%", background: ACC, color: ONACC, display: "flex", alignItems: "center", justifyContent: "center", fontFamily: FAMILY, fontSize: 52, fontWeight: 800, scale: p, boxShadow: "0 0 40px rgba(255,255,255,.18)" }}>
      {n}
    </div>
  );
};

// ---------------------------------------------------------------- gancho
const Gancho: React.FC<DomumProps> = ({ gancho, fondo, marca, etiqueta, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const words = gancho.split(" ");
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.35} zoomFrom={1} />
      <Particles seed={seed} />
      <div style={{ position: "absolute", left: 80, top: 360, fontFamily: FAMILY, fontSize: 28, fontWeight: 700, letterSpacing: 7, color: HI, textTransform: "uppercase", opacity: interpolate(frame, [4, 20], [0, 1], clampOpts) }}>{etiqueta}</div>
      <div style={{ position: "absolute", left: 80, top: 430, opacity: interpolate(frame, [8, 26], [0, 1], clampOpts), translate: `0px ${interpolate(frame, [8, 30], [40, 0], { ...clampOpts, easing: ease })}px` }}>
        <Chip marca={marca} height={96} />
      </div>
      <div style={{ position: "absolute", left: 80, right: 80, top: 640, display: "flex", flexWrap: "wrap", columnGap: 24, fontFamily: FAMILY, fontSize: 108, lineHeight: 1.08, fontWeight: 800, letterSpacing: -3, color: "#fff", textShadow: "0 6px 40px rgba(0,16,40,.8)" }}>
        {words.map((w, i) => {
          const p = spring({ frame: frame - 24 - i * 4, fps, config: { damping: 14, stiffness: 150 } });
          const hl = w.startsWith("proveedores");
          const inner = hl ? (
            <Highlight color="rgba(0, 209, 181, 0.55)" progress={interpolate(frame, [58, 82], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" })}>{w}</Highlight>
          ) : w;
          return (
            <span key={i} style={{ display: "inline-block", opacity: interpolate(p, [0, 0.6], [0, 1], clampOpts), translate: `0px ${interpolate(p, [0, 1], [70, 0])}px` }}>{inner}</span>
          );
        })}
      </div>
      <Mascota left={610} top={1340} height={470} delay={60} from="bottom" />
    </AbsoluteFill>
  );
};

// ---------------------------------------------------------------- 1. enlace único que vence
const Enlace: React.FC<DomumProps> = ({ p1, fondo, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const secs = Math.round(interpolate(frame, [30, 105], [30, 6], clampOpts));
  const frac = interpolate(frame, [30, 105], [1, 0.2], clampOpts);
  const R = 170, C = 2 * Math.PI * R;
  const pop = spring({ frame: frame - 14, fps, config: { damping: 14, stiffness: 120 } });
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.6} zoomFrom={1.08} />
      <Particles seed={seed + 2} />
      <Badge n={1} />
      <Words text={p1} size={72} delay={6} top={410} />
      <div style={{ position: "absolute", left: 80, top: 730, width: 920, height: 650, ...glass, scale: interpolate(pop, [0, 1], [0.85, 1]), opacity: interpolate(pop, [0, 0.6], [0, 1], clampOpts) }}>
        <div style={{ height: 78, display: "flex", alignItems: "center", gap: 14, padding: "0 28px", borderBottom: "2px solid rgba(255,255,255,.1)" }}>
          {[0, 1, 2].map((i) => <div key={i} style={{ width: 16, height: 16, borderRadius: "50%", background: "rgba(255,255,255,.35)" }} />)}
          <div style={{ marginLeft: 20, flex: 1, height: 44, borderRadius: 22, background: "rgba(255,255,255,.1)", display: "flex", alignItems: "center", gap: 12, padding: "0 20px", fontFamily: FAMILY, fontSize: 26, fontWeight: 600, color: "#fff" }}>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1" /></svg>
            enlace único
          </div>
        </div>
        <svg width="920" height="560" viewBox="0 0 920 560">
          <circle cx="460" cy="270" r={R} fill="none" stroke="rgba(255,255,255,.14)" strokeWidth="22" />
          <circle cx="460" cy="270" r={R} fill="none" style={{ stroke: "var(--acc, #3A84BD)" }} strokeWidth="22" strokeLinecap="round" strokeDasharray={C} strokeDashoffset={C * (1 - frac)} transform="rotate(-90 460 270)" />
          <text x="460" y="298" textAnchor="middle" fontFamily={FAMILY} fontSize="92" fontWeight="800" fill="#fff">{`00:${String(secs).padStart(2, "0")}`}</text>
          <text x="460" y="500" textAnchor="middle" fontFamily={FAMILY} fontSize="30" fontWeight="700" letterSpacing="6" style={{ fill: "var(--hi, #6fe3f5)" }}>EL ENLACE VENCE</text>
        </svg>
      </div>
      <div style={{ position: "absolute", left: 700, top: 650, padding: "12px 34px", borderRadius: 999, background: "rgba(0,0,0,.55)", border: "2px solid rgba(255,255,255,.25)", fontFamily: FAMILY, fontSize: 40, fontWeight: 800, color: "#fff", opacity: interpolate(frame, [20, 34], [0, 1], clampOpts) }}>
        <CrossedOff color="#FF6B6B" progress={interpolate(frame, [34, 56], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" })}>VPN</CrossedOff>
      </div>
      <Mascota left={700} top={1430} height={380} delay={36} from="bottom" />
    </AbsoluteFill>
  );
};

// ---------------------------------------------------------------- 2. contenedor aislado, grabada, en vivo
const Sesion: React.FC<DomumProps> = ({ p2, fondo, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const pop = spring({ frame: frame - 14, fps, config: { damping: 14, stiffness: 120 } });
  const rec = 0.55 + 0.45 * Math.sin(frame / 5);
  const t = Math.round(interpolate(frame, [20, 110], [0, 47], clampOpts));
  const cx = interpolate(frame, [20, 60, 90, 120], [200, 520, 360, 700], clampOpts);
  const cy = interpolate(frame, [20, 60, 90, 120], [900, 990, 1090, 1010], clampOpts);
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.6} zoomFrom={1.12} />
      <Particles seed={seed + 4} />
      <Badge n={2} />
      <Words text={p2} size={62} delay={6} top={410} />
      <div style={{ position: "absolute", left: 80, top: 770, width: 920, height: 640, borderRadius: 44, border: `4px dashed ${ACC}`, background: "rgba(0,0,0,.22)", opacity: interpolate(pop, [0, 0.6], [0, 1], clampOpts), scale: interpolate(pop, [0, 1], [0.88, 1]) }}>
        <div style={{ position: "absolute", left: 34, top: -26, padding: "8px 24px", borderRadius: 999, background: ACC, color: ONACC, fontFamily: FAMILY, fontSize: 24, fontWeight: 800, letterSpacing: 3, textTransform: "uppercase" }}>Contenedor aislado</div>
        <div style={{ position: "absolute", right: 34, top: 34, display: "flex", alignItems: "center", gap: 12, fontFamily: FAMILY, fontSize: 28, fontWeight: 800, color: "#fff" }}>
          <div style={{ width: 24, height: 24, borderRadius: "50%", background: "#FF4D4D", opacity: rec, boxShadow: "0 0 24px #FF4D4D" }} />
          REC · EN VIVO
        </div>
        <div style={{ position: "absolute", left: 60, top: 120, width: 800, height: 350, borderRadius: 26, background: "rgba(255,255,255,.08)", border: "2px solid rgba(255,255,255,.14)", padding: 34 }}>
          {[0.9, 0.62, 0.78, 0.45].map((w, i) => (
            <div key={i} style={{ height: 22, width: `${w * interpolate(frame, [26 + i * 8, 50 + i * 8], [0, 100], clampOpts)}%`, borderRadius: 11, background: "rgba(255,255,255,.28)", marginBottom: 34 }} />
          ))}
        </div>
        <div style={{ position: "absolute", left: 60, right: 60, bottom: 40, height: 16, borderRadius: 8, background: "rgba(255,255,255,.16)" }}>
          <div style={{ width: `${interpolate(frame, [20, 110], [4, 92], clampOpts)}%`, height: "100%", borderRadius: 8, background: ACC }} />
        </div>
        <div style={{ position: "absolute", left: 60, bottom: 68, fontFamily: FAMILY, fontSize: 24, fontWeight: 700, color: HI }}>{`00:${String(t).padStart(2, "0")} grabando`}</div>
      </div>
      <div style={{ position: "absolute", left: cx - 20, top: cy - 20 - 0, width: 40, height: 40, borderRadius: "50%", border: `4px solid ${HI}`, background: "rgba(255,255,255,.3)", boxShadow: "0 0 30px rgba(255,255,255,.4)" }} />
      <Mascota left={700} top={1440} height={370} delay={36} from="bottom" />
    </AbsoluteFill>
  );
};

// ---------------------------------------------------------------- 3. control por sistema, horario y ubicación
const Row: React.FC<{ i: number; titulo: string; sub: string; icon: React.ReactNode }> = ({ i, titulo, sub, icon }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const enter = spring({ frame: frame - 18 - i * 14, fps, config: { damping: 15, stiffness: 130 } });
  const on = spring({ frame: frame - 50 - i * 16, fps, config: { damping: 13, stiffness: 160 } });
  return (
    <div style={{ position: "absolute", left: 80, right: 80, top: 730 + i * 205, height: 175, ...glass, borderRadius: 36, display: "flex", alignItems: "center", gap: 30, padding: "0 40px", opacity: interpolate(enter, [0, 0.5], [0, 1], clampOpts), translate: `${interpolate(enter, [0, 1], [160, 0])}px 0px` }}>
      <div style={{ flex: "none", width: 92, height: 92, borderRadius: "50%", background: ACC, display: "flex", alignItems: "center", justifyContent: "center" }}>{icon}</div>
      <div style={{ flex: 1 }}>
        <div style={{ fontFamily: FAMILY, fontSize: 50, fontWeight: 800, color: "#fff" }}>{titulo}</div>
        <div style={{ fontFamily: FAMILY, fontSize: 28, fontWeight: 500, color: "rgba(255,255,255,.75)" }}>{sub}</div>
      </div>
      <div style={{ flex: "none", width: 132, height: 70, borderRadius: 35, background: `color-mix(in srgb, ${ACC} ${Math.round(on * 100)}%, rgba(255,255,255,.2))`, position: "relative" }}>
        <div style={{ position: "absolute", top: 7, left: 7 + on * 62, width: 56, height: 56, borderRadius: "50%", background: "#fff", boxShadow: "0 4px 12px rgba(0,0,0,.35)" }} />
      </div>
    </div>
  );
};

const Control: React.FC<DomumProps> = ({ p3, fondo, seed }) => {
  const ic = (d: React.ReactNode) => <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke={ONACC} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{d}</svg>;
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.62} zoomFrom={1.06} />
      <Particles seed={seed + 6} />
      <Badge n={3} />
      <Words text={p3} size={72} delay={6} top={410} />
      <Row i={0} titulo="Sistema" sub="qué puede abrir" icon={ic(<><rect x="3" y="4" width="18" height="12" rx="2" /><path d="M8 20h8M12 16v4" /></>)} />
      <Row i={1} titulo="Horario" sub="cuándo puede entrar" icon={ic(<><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>)} />
      <Row i={2} titulo="Ubicación" sub="desde dónde" icon={ic(<><path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z" /><circle cx="12" cy="10" r="2.5" /></>)} />
      <Mascota left={700} top={1450} height={360} delay={40} from="bottom" />
    </AbsoluteFill>
  );
};

// ---------------------------------------------------------------- cierre
const Cierre: React.FC<DomumProps> = ({ cierre, marca, fondo, seed }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const pop = (d: number) => spring({ frame: frame - d, fps, config: { damping: 12 } });
  return (
    <AbsoluteFill>
      <Backdrop fondo={fondo} dim={0.5} zoomFrom={1.05} />
      <Particles seed={seed + 9} />
      <Words text={cierre} size={104} delay={4} top={430} />
      <div style={{ position: "absolute", left: 80, width: 600, top: 960, height: 130, borderRadius: 65, background: `linear-gradient(135deg, ${ACC}, var(--acc2, #3f86d0))`, color: ONACC, display: "flex", alignItems: "center", justifyContent: "center", fontFamily: FAMILY, fontSize: 50, fontWeight: 800, boxShadow: `0 0 ${40 + Math.sin(frame / 5) * 25}px rgba(255,255,255,.25)`, scale: pop(26) * (1 + Math.sin(frame / 6) * 0.015), transformOrigin: "left center" }}>#CPNnetPartners</div>
      <div style={{ position: "absolute", left: 80, top: 1160, opacity: interpolate(frame, [40, 54], [0, 1], clampOpts) }}><Chip marca={marca} height={84} /></div>
      <div style={{ position: "absolute", left: 80, top: 1350, display: "flex" }}><Flags size={72} delay={50} /></div>
      <Mascota left={590} top={1130} height={650} delay={30} />
    </AbsoluteFill>
  );
};

export const SeguraDomum: React.FC<DomumProps> = (props) => {
  const [handle] = useState(() => delayRender("Cargando fuentes"));
  useEffect(() => { fontsReady.then(() => continueRender(handle)); }, [handle]);
  const { durationInFrames } = useVideoConfig();
  const T = 15;
  const b = props.brand;
  const vars = { "--hi": b.hi, "--acc": b.acc, "--acc2": b.acc2, "--base": b.base, "--dark": b.dark, "--tinte": b.tinte, "--onacc": b.onacc } as React.CSSProperties;
  return (
    <AbsoluteFill style={{ backgroundColor: NAVY, fontFamily: FAMILY, ...vars }}>
      <TransitionSeries>
        <TransitionSeries.Sequence name="Gancho" durationInFrames={120}><Gancho {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-right" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="1 Enlace único" durationInFrames={120}><Enlace {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-bottom" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="2 Sesión aislada" durationInFrames={120}><Sesion {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={slide({ direction: "from-right" })} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="3 Control" durationInFrames={120}><Control {...props} /></TransitionSeries.Sequence>
        <TransitionSeries.Transition presentation={fade()} timing={linearTiming({ durationInFrames: T })} />
        <TransitionSeries.Sequence name="Cierre" durationInFrames={120}><Cierre {...props} /></TransitionSeries.Sequence>
      </TransitionSeries>
      <Overlay total={durationInFrames} />
    </AbsoluteFill>
  );
};
